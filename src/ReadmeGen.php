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

        $this->writeNl($stream, $template);
        $this->writeMarkdownDocumentation($stream);
    }

    private function writeMarkdownDocumentation($stream): void
    {
        $this->writeNl($stream, '## Documentation');

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

    private function writeMarkdownForMethod($stream, ReflectionMethod $method, Context $context): void
    {
        $this->writeNl($stream, sprintf('#### %s()', $method->getName()));

        $tags = '';
        $hasTagReturn = false;
        $docComment = $method->getDocComment();
        if ($docComment) {
            $docComment = $this->docBlockFactory->create($docComment, $context);

            foreach ($docComment->getTags() as $tag) {
                $tags .= '>     '.''.sprintf(
                    '@%-8s %s ',
                    $tag->getName(),
                    str_replace(["\n", "\r"], ' ', mb_substr($tag->render(), \strlen($tag->getName()) + 2)),
                ).''.PHP_EOL;

                if ('return' === $tag->getName()) {
                    $hasTagReturn = true;
                }
            }

            $docCommentText = str_replace(["\n", "\r"], ' ', $docComment->getSummary());
            $docCommentText .= PHP_EOL.PHP_EOL.$docComment->getDescription();

            $this->writeNl($stream, $docCommentText);
        }

        $this->writeNoNl($stream, '>    **``'.$method->getName().'(');

        $paramString = '';
        foreach ($method->getParameters() as $param) {
            $paramString .= $param->getType().' $'.$param->getName();

            if ($param->isDefaultValueAvailable()) {
                if ('string' === (string) $param->getType()) {
                    $paramString .= ' = \''.$param->getDefaultValue().'\'';
                } elseif ('array' === (string) $param->getType()) {
                    $paramString .= ' = []';
                } else {
                    $paramString .= ' = '.$param->getDefaultValue();
                }
            }
            $paramString .= ', ';
        }

        $this->writeNoNl($stream, trim($paramString, ', '));
        $this->writeNoNl($stream, '): '.($method->getReturnType() ?? 'void').'``**'.PHP_EOL.'>     '.PHP_EOL.$tags);

        if (!$hasTagReturn) {
            $this->write($stream, '>     @return   '.($method->getReturnType() ?? 'void').'');
        }

        $this->writeNl($stream, '');
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

            $this->writeMarkdownForMethod($stream, $method, $context);
        }

        $this->writeNl($stream, '');
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
