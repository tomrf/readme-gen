<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen\Formatter;

use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionMethod;

class DummyFormatter extends AbstractFormatter
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
            $formatted .= sprintf(" - %s\n", $class);
            foreach ($methods as $method) {
                $formatted .= sprintf("   - %s\n", $method);
            }
        }

        return $formatted;
    }

    public function formatClass(ReflectionClass $reflection): string
    {
        $context = $this->contextFactory->createFromReflector($reflection);
        $docBlock = $this->docBlockFactory->create($reflection, $context);

        $formatted = sprintf(
            "%s::class\n\n",
            $reflection->getName(),
        );

        if ('' !== $docBlock->getSummary()) {
            $formatted .= sprintf("%s\n\n", $docBlock->getSummary());
        }

        if ('' !== (string) $docBlock->getDescription()) {
            $formatted .= sprintf('%s', (string) $docBlock->getDescription());
        }

        return sprintf("%s\n\n", $formatted);
    }

    public function formatMethod(ReflectionMethod $reflection, string $methodDefinition, array $tags): string
    {
        $context = $this->contextFactory->createFromReflector($reflection);
        $docBlock = $this->docBlockFactory->create($reflection, $context);

        $formatted = sprintf("%s()\n\n", $reflection->getName());

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
            "%s\n%s\n\n",
            $methodDefinition,
            $tagsString,
        );

        return $formatted;
    }
}
