<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen;

use HaydenPierce\ClassFinder\ClassFinder;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Tomrf\ReadmeGen\Interface\ReadmeFormatterInterface;

/**
 * ReadmeGen.
 */
class ReadmeGen
{
    private string $projectRoot;
    private string $vendorName;
    private string $projectName;

    private ComposerJsonParser $composerJsonParser;

    private DocBlockFactoryInterface $docBlockFactory;
    private ContextFactory $contextFactory;

    public function __construct(string $projectRoot)
    {
        if (!str_ends_with($projectRoot, '/')) {
            $projectRoot = sprintf('%s/', $projectRoot);
        }

        if (!file_exists($projectRoot)) {
            throw new RuntimeException(sprintf('No such file or directory: %s', $projectRoot));
        }

        if (!is_dir($projectRoot)) {
            throw new RuntimeException(sprintf('Not a directory: %s', $projectRoot));
        }

        if (!file_exists(sprintf('%scomposer.json', $projectRoot))) {
            throw new RuntimeException(sprintf(
                'Could not parse composer.json: file not found: %scomposer.json',
                $projectRoot
            ));
        }

        $this->projectRoot = $projectRoot;
        $this->composerJsonParser = new ComposerJsonParser(sprintf('%s/composer.json', $projectRoot));

        if (!$this->composerJsonParser->has('name')) {
            throw new RuntimeException(sprintf(
                'Composer JSON missing required property "name": %scomposer.sjon',
                $projectRoot
            ));
        }

        $this->parsePackageName($this->composerJsonParser->get('name'));

        $this->autoloadProject();
        ClassFinder::setAppRoot($this->projectRoot);

        $this->docBlockFactory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
        $this->contextFactory = new \phpDocumentor\Reflection\Types\ContextFactory();
    }

    public function generate(
        ReadmeFormatterInterface $formatter,
        string $templateFilename
    ): string {
        $docToc = [];
        $documentation = '';

        foreach ($this->getAutoloadNamespaces() as $namespace) {
            $namespace = $namespace['namespace'];
            $classes = ClassFinder::getClassesInNamespace($namespace, ClassFinder::RECURSIVE_MODE);

            foreach ($classes as $class) {
                if ($this->isClassExcluded($class)) {
                    continue;
                }

                $docToc[$class] = [];

                $reflectionClass = new ReflectionClass($class);
                $documentation .= $formatter->formatClass($reflectionClass);
                foreach ($reflectionClass->getMethods() as $method) {
                    if (!$method->isPublic()) {
                        continue;
                    }

                    $docToc[$class][] = $method->getName();

                    $documentation .= $formatter->formatMethod(
                        $method,
                        $this->getMethodDefinition($method),
                        $this->getTags($method)
                    );
                }
            }
        }

        $docTocFormatted = $formatter->formatToc($docToc);

        $template = file_get_contents($templateFilename);

        $templateCompiler = new TemplateCompiler();

        return $templateCompiler->compile(
            new ComposerJsonParser(sprintf('%s/composer.json', $this->projectRoot)),
            $template,
            [
                'documentation_body' => $documentation,
                'documentation_toc' => $docTocFormatted,
                'package_vendor' => $this->vendorName,
                'package_project' => $this->projectName,
                'date' => date('c'),
            ]
        );
    }

    protected function getTags($objectOrString, Context $context = null): array
    {
        if (method_exists($objectOrString, 'getDocComment')) {
            if (!$objectOrString->getDocComment()) {
                return [];
            }
        }

        $tags = [];
        $docBlock = $this->docBlockFactory->create($objectOrString, $context);

        foreach ($docBlock->getTags() as $tag) {
            $tags[] = [
                $tag->getName() => str_replace(
                    ["\n", "\r"],
                    ' ',
                    (string) $tag
                ),
            ];
        }

        return $tags;
    }

    protected function getMethodDefinition(ReflectionMethod $method): string
    {
        $parametersString = '';

        $parameters = $method->getParameters();
        foreach ($parameters as $n => $param) {
            $type = $param->getType() ? sprintf('%s ', $param->getType()) : '';
            $parametersString .= sprintf('    %s$%s', $type, $param->getName());

            if ($param->isDefaultValueAvailable()) {
                if ('array' === (string) $param->getType()) {
                    $parametersString .= ' = []';
                } elseif (str_contains((string) $param->getType(), 'string')) {
                    $parametersString .= sprintf(' = \'%s\'', $param->getDefaultValue());
                } else {
                    $parametersString .= sprintf(' = %s', $param->getDefaultValue());
                }
            }

            if (array_key_last($parameters) !== $n) {
                $parametersString .= ','.PHP_EOL;
            }
        }

        return sprintf(
            "%s function %s(\n%s\n): %s",
            $this->getAccessForReflectionMethod($method),
            $method->getName(),
            $parametersString,
            ($method->getReturnType() ?? 'void')
        );
    }

    protected function getAccessForReflectionMethod(ReflectionMethod $method): string
    {
        return trim(sprintf(
            '%s%s%s%s%s',
            $method->isAbstract() ? 'abstract ' : '',
            $method->isPrivate() ? 'private ' : '',
            $method->isProtected() ? 'protected ' : '',
            $method->isPublic() ? 'public ' : '',
            $method->isStatic() ? 'static ' : '',
        ));
    }

    private function parsePackageName(string $name): void
    {
        $match = preg_match('/^([a-z0-9_.-]+)*\/([a-z0-9_.-]+)*$/', $name, $matches);
        if (1 !== $match) {
            throw new RuntimeException(sprintf(
                'Could not parse vendor and project name name from composer.json package name: "%s"',
                $name
            ));
        }

        $this->vendorName = $matches[1];
        $this->projectName = $matches[2];
    }

    private function autoloadProject(): void
    {
        require $this->projectRoot.'/vendor/autoload.php';
    }

    private function getAutoloadNamespaces(string $key = 'autoload'): array
    {
        $array = [];

        $autoload = $this->composerJsonParser->get($key);
        $mechanism = 'psr-4';

        if (!isset($autoload->{$mechanism})) {
            return $array;
        }

        foreach ($autoload->{$mechanism} as $namespace => $source) {
            $array[] = [
                'namespace' => trim($namespace, '\\'),
                'source' => $source,
            ];
        }

        return $array;
    }

    private function isNamespaceExcluded(string $namespace): bool
    {
        if (!$this->composerJsonParser->has('autoload-dev')) {
            return false;
        }

        foreach ($this->getAutoloadNamespaces('autoload-dev') as $excluded) {
            if ($namespace === $excluded['namespace']) {
                return true;
            }
        }

        return false;
    }

    private function isSourceExcluded(string $source): bool
    {
        if (!$this->composerJsonParser->has('autoload-dev')) {
            return false;
        }

        $source = str_replace($this->projectRoot, '', $source);

        foreach ($this->getAutoloadNamespaces('autoload-dev') as $excluded) {
            if (str_starts_with($source, $excluded['source'])) {
                return true;
            }
        }

        return false;
    }

    private function isClassExcluded(string $classname): bool
    {
        $try = '';
        foreach (explode('\\', $classname) as $part) {
            $try = trim($try.'\\'.$part, '\\');

            if ($this->isNamespaceExcluded($try)) {
                return true;
            }
        }

        $reflection = new ReflectionClass($classname);

        if ($this->isSourceExcluded($reflection->getFileName())) {
            return true;
        }

        if ($this->isClassInternal($reflection)) {
            return true;
        }

        return false;
    }

    private function isClassInternal(ReflectionClass $reflection): bool
    {
        $docComment = $reflection->getDocComment();

        if (false === $docComment) {
            return false;
        }

        if (str_contains($docComment, '@internal')) {
            return true;
        }

        return false;
    }
}
