<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen;

use HaydenPierce\ClassFinder\ClassFinder;
use MCStreetguy\ComposerParser\ComposerJson;
use MCStreetguy\ComposerParser\Factory as ComposerParser;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * ReadmeGen.
 */
class ReadmeGen
{
    private string $projectRoot;
    private string $vendorName;
    private string $projectName;

    private ComposerJson $composerJson;

    private DocBlockFactory $docBlockFactory;
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
        $this->composerJson = ComposerParser::parse(sprintf('%scomposer.json', $projectRoot));
        $this->docBlockFactory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
        $this->contextFactory = new \phpDocumentor\Reflection\Types\ContextFactory();

        $match = preg_match('/^([a-z0-9_.-]+)*\/([a-z0-9_.-]+)*$/', $this->composerJson->getName(), $matches);
        if (1 !== $match) {
            throw new RuntimeException(sprintf(
                'Could not parse vendor and project name name from composer.json package name: "%s"',
                $this->composerJson->getName()
            ));
        }

        $this->vendorName = $matches[1];
        $this->projectName = $matches[2];

        $this->autoloadProject();
        ClassFinder::setAppRoot($this->projectRoot);
    }

    public function generate(
        ReadmeFormatterInterface $formatter,
        string $templateFilename
    ): string {
        $documentation = '';

        foreach ($this->getAutoloadNamespaces() as $namespace) {
            $namespace = trim($namespace['namespace'], '\\');
            $classes = ClassFinder::getClassesInNamespace($namespace, ClassFinder::RECURSIVE_MODE);

            foreach ($classes as $class) {
                if ($this->isClassExcluded($class)) {
                    continue;
                }

                $reflectionClass = new ReflectionClass($class);

                $documentation .= $formatter->formatClass($reflectionClass);

                foreach ($reflectionClass->getMethods() as $method) {
                    if (!$method->isPublic()) {
                        continue;
                    }

                    $documentation .= $formatter->formatMethod($method);
                }
            }
        }

        $template = file_get_contents($templateFilename);

        return $this->compileTemplate($template, [
            'documentation' => $documentation,
        ]);
    }

    public function writeMarkdown($stream): void
    {
        $template = file_get_contents('resources/template.md');

        // set basic composer package keys
        foreach (['name', 'type', 'description', 'homepage', 'license'] as $key) {
            $value = $this->composerJson->{$key};

            if (\is_array($value)) {
                $value = trim(implode(', ', $value), ', ');
            }

            $template = str_replace(
                sprintf(':package_%s', $key),
                $value,
                $template
            );
        }

        // set extra keys
        foreach ($this->composerJson->getExtra() as $key => $value) {
            if (\is_array($value)) {
                $value = implode(PHP_EOL, $value);
            }

            $template = str_replace(
                sprintf(':package_extra_%s', $key),
                $value,
                $template
            );
        }

        // set special composer package keys
        $template = str_replace(':package_vendor', $this->vendorName, $template);
        $template = str_replace(':package_project', $this->projectName, $template);

        // hax tmp hax
        $template = str_replace(':documentation', '', $template);

        $this->writeNl($stream, $template);
        $this->writeMarkdownDocumentation($stream);
    }

    private function compileTemplate(string $template, array $extra = []): string
    {
        $return = $template;

        // set basic composer package keys
        foreach (['name', 'type', 'description', 'homepage', 'license'] as $key) {
            $value = $this->composerJson->{$key};
            if (\is_array($value)) {
                $value = trim(implode(', ', $value), ', ');
            }
            $return = str_replace(sprintf(':package_%s', $key), $value, $return);
        }

        // set composer extra keys
        foreach ($this->composerJson->getExtra() as $key => $value) {
            if (\is_array($value)) {
                $value = implode(PHP_EOL, $value);
            }
            $return = str_replace(sprintf(':package_extra_%s', $key), $value, $return);
        }

        // set special composer package keys
        $return = str_replace(':package_vendor', $this->vendorName, $return);
        $return = str_replace(':package_project', $this->projectName, $return);

        // set $extra keys
        foreach ($extra as $key => $value) {
            if (\is_array($value)) {
                $value = implode(PHP_EOL, $value);
            }
            $return = str_replace(sprintf(':%s', $key), $value, $return);
        }

        return $return;
    }

    private function writeMarkdownDocumentation($stream): void
    {
        foreach ($this->getAutoloadNamespaces() as $namespace) {
            $namespace = trim($namespace['namespace'], '\\');
            $classes = ClassFinder::getClassesInNamespace($namespace, ClassFinder::RECURSIVE_MODE);

            foreach ($classes as $class) {
                if ($this->isClassExcluded($class)) {
                    continue;
                }

                $this->writeMarkdownForClass($stream, $class);
            }
        }
    }

    private function writeMarkdownForClass($stream, string $class): void
    {
        $reflection = new ReflectionClass($class);
        $docComment = $reflection->getDocComment();
        $context = $this->contextFactory->createFromReflector($reflection);

        if ($docComment) {
            $docComment = $this->docBlockFactory->create($docComment, $context);
            $docComment = str_replace(["\n", "\r"], ' ', $docComment->getSummary());
        } else {
            $docComment = '*No description for class*';
        }

        $this->writeNl($stream, sprintf('### 📂 %s::class', $reflection->getName()));
        $this->writeNl($stream, sprintf('%s', $docComment));

        foreach ($reflection->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            $this->writeMarkdownForMethod($stream, $method);
        }

        $this->writeNl($stream, '***');
    }

    private function writeMarkdownForMethod($stream, ReflectionMethod $method): void
    {
        $context = $this->contextFactory->createFromReflector($method);
        $docBlock = $this->docBlockFactory->create($method, $context);

        // method headline
        $this->writeNl($stream, sprintf('#### %s()', $method->getName()));

        // summary
        if ('' !== $docBlock->getSummary()) {
            $this->writeNl($stream, str_replace(["\n", "\r"], ' ', $docBlock->getSummary()));
        }

        // description
        if ('' !== (string) $docBlock->getDescription()) {
            $this->writeNl($stream, str_replace(["\n", "\r"], ' ', (string) $docBlock->getDescription()));
        }

        // method definition and tags
        $this->writeNl($stream, sprintf(
            "```php\n%s\n%s```",
            $this->getMethodDefinition($method),
            $this->getMethodTagsString($method) ? sprintf("\n%s", $this->getMethodTagsString($method)) : '',
        ));
    }

    private function getTags($objectOrString, Context $context = null): array
    {
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

    private function getMethodTagsString(ReflectionMethod $method, Context $context = null): string
    {
        $tagsString = '';

        foreach ($this->getTags($method, $context) as $tag) {
            $tagsString .= sprintf("@%-8s %s\n", key($tag), (string) $tag[key($tag)]);
        }

        return $tagsString;
    }

    private function getAccessForReflectionMethod(ReflectionMethod $method): string
    {
        return trim(sprintf(
            '%s%s%s%s%s%s',
            $method->isAbstract() ? 'abstract ' : '',
            $method->isStatic() ? 'static ' : '',
            $method->isPrivate() ? 'private ' : '',
            $method->isProtected() ? 'protected ' : '',
            $method->isPublic() ? 'public ' : '',
            $method->isInternal() ? 'private ' : '',
        ));
    }

    private function getMethodDefinition(ReflectionMethod $method): string
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

    private function writeNl($stream, string $data): int
    {
        return $this->write($stream, $data.PHP_EOL);
    }

    private function write($stream, string $data): int
    {
        return (int) fwrite($stream, $data.PHP_EOL);
    }

    private function writeNoNl($stream, string $data): int
    {
        return (int) fwrite($stream, $data);
    }

    private function autoloadProject(): void
    {
        require $this->projectRoot.'/vendor/autoload.php';
    }

    private function getAutoloadNamespaces(): array
    {
        return $this->composerJson->getAutoload()->getPsr4()->getNamespaces();
    }

    private function isNamespaceExcluded(string $namespace): bool
    {
        foreach ($this->composerJson->getAutoloadDev()->getPsr4()->getNamespaces() as $excluded) {
            if ($namespace === trim($excluded['namespace'], '\\')) {
                return true;
            }
        }

        return false;
    }

    private function isSourceExcluded(string $source): bool
    {
        $source = str_replace($this->projectRoot, '', $source);

        foreach ($this->composerJson->getAutoloadDev()->getPsr4()->getNamespaces() as $excluded) {
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
        $context = $this->contextFactory->createFromReflector($reflection);

        if (!$docComment) {
            return false;
        }

        $docComment = $this->docBlockFactory->create($docComment, $context);
        if (\count($docComment->getTagsByName('internal')) > 0) {
            return true;
        }

        return false;
    }
}
