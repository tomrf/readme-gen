<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen\Formatter;

use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionMethod;

class MarkdownFormatter extends AbstractFormatter
{
    public function __construct(
        protected DocBlockFactoryInterface $docBlockFactory,
        protected ContextFactory $contextFactory
    ) {
    }

    public function formatToc(array $toc): string
    {
        $formatted = '';

        foreach ($toc as $class => $methods) {
            $formatted .= sprintf(" - [%s](#%s)\n", $class, sprintf('-%sclass', str_replace('\\', '', strtolower($class))));
            foreach ($methods as $method) {
                $formatted .= sprintf("   - [%s](#%s)\n", $method, strtolower($method));
            }
        }

        return $formatted;
    }

    public function formatClass(ReflectionClass $reflection): string
    {
        $formatted = sprintf(
            "### 📂 %s::class\n\n",
            $reflection->getName(),
        );

        if (false === $reflection->getDocComment()) {
            return $formatted;
        }

        $context = $this->contextFactory->createFromReflector($reflection);
        $docBlock = $this->docBlockFactory->create($reflection, $context);

        if ('' !== $docBlock->getSummary()) {
            $formatted .= sprintf("%s\n\n", $docBlock->getSummary());
        }

        if ('' !== (string) $docBlock->getDescription()) {
            $formatted .= sprintf('%s', (string) $docBlock->getDescription());
        }

        return sprintf("\n***\n\n%s", $formatted);
    }

    public function formatMethod(ReflectionMethod $reflection, string $methodDefinition, array $tags): string
    {
        $formatted = sprintf("#### %s()\n\n", $reflection->getName());

        if (false === $reflection->getDocComment()) {
            return sprintf('%s%s', $formatted, sprintf(
                "```php\n%s\n```\n\n",
                $methodDefinition
            ));
        }

        $context = $this->contextFactory->createFromReflector($reflection);
        $docBlock = $this->docBlockFactory->create($reflection, $context);

        if ('' !== $docBlock->getSummary()) {
            $formatted .= sprintf("%s\n\n", $docBlock->getSummary());
        }

        if ('' !== (string) $docBlock->getDescription()) {
            $formatted .= sprintf("%s\n\n", (string) $docBlock->getDescription());
        }

        // tags
        $tagsString = '';
        foreach ($tags as $tag) {
            $tagsString .= sprintf("@%-8s %s\n", key($tag), (string) $tag[key($tag)]);
        }

        $formatted .= sprintf(
            "```php\n%s\n%s```\n\n",
            $methodDefinition,
            $tagsString,
        );

        return $formatted;
    }
}
