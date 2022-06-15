<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen\Interface;

use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionMethod;

/**
 * ReadmeFormatterInterface.
 */
interface ReadmeFormatterInterface
{
    public function __construct(
        DocBlockFactoryInterface $docBlockFactory,
        ContextFactory $contextFactory
    );

    public function formatToc(array $structure): string;

    public function formatMethod(ReflectionMethod $reflection, string $methodDefinition, array $tags): string;

    public function formatClass(ReflectionClass $reflection): string;
}
