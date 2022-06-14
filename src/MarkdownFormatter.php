<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen;

use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionMethod;

class MarkdownFormatter implements ReadmeFormatterInterface
{
    public function __construct(
        private DocBlockFactoryInterface $docBlockFactory,
        private ContextFactory $contextFactory
    ) {
        // ..
    }

    public function formatClass(ReflectionClass $reflection): string
    {
        $context = $this->contextFactory->createFromReflector($reflection);
        $docBlock = $this->docBlockFactory->create($reflection, $context);

        $formatted = sprintf(
            "### 📂 %s::class\n\n",
            $reflection->getName(),
        );

        if ('' !== $docBlock->getSummary()) {
            $formatted .= sprintf("%s\n\n", $docBlock->getSummary());
        }

        if ('' !== (string) $docBlock->getDescription()) {
            $formatted .= sprintf("%s\n\n", (string) $docBlock->getDescription());
        }

        return $formatted;
    }

    public function formatMethod(ReflectionMethod $reflection): string
    {
        $context = $this->contextFactory->createFromReflector($reflection);
        $docBlock = $this->docBlockFactory->create($reflection, $context);

        $formatted = sprintf("#### %s()\n\n", $reflection->getName());

        if ('' !== $docBlock->getSummary()) {
            $formatted .= sprintf("%s\n\n", $docBlock->getSummary());
        }

        if ('' !== (string) $docBlock->getDescription()) {
            $formatted .= sprintf("%s\n\n", (string) $docBlock->getDescription());
        }

        $formatted .= sprintf(
            "```php\n%s\n%s```\n\n",
            $this->getMethodDefinition($reflection),
            $this->getMethodTagsString($reflection) ? sprintf("\n%s", $this->getMethodTagsString($reflection)) : '',
        );

        return $formatted;
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
}
