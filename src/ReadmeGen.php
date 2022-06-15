<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen;

use HaydenPierce\ClassFinder\ClassFinder;
use MCStreetguy\ComposerParser\ComposerJson;
use MCStreetguy\ComposerParser\Factory as ComposerParser;
use ReflectionClass;
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
        $docToc = [];
        $documentation = '';

        foreach ($this->getAutoloadNamespaces() as $namespace) {
            $namespace = trim($namespace['namespace'], '\\');
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

                    $documentation .= $formatter->formatMethod($method);
                }
            }
        }

        $docTocFormatted = $formatter->formatToc($docToc);

        $template = file_get_contents($templateFilename);

        return $this->compileTemplate($template, [
            'documentation_body' => $documentation,
            'documentation_toc' => $docTocFormatted,
            'date' => date('c'),
        ]);
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

        if (false === $docComment) {
            return false;
        }

        if (str_contains($docComment, '@internal')) {
            return true;
        }

        return false;
    }
}
