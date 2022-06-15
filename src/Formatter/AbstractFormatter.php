<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen\Formatter;

use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\ContextFactory;
use Tomrf\ReadmeGen\Interface\ReadmeFormatterInterface;

abstract class AbstractFormatter implements ReadmeFormatterInterface
{
    protected DocBlockFactoryInterface $docBlockFactory;
    protected ContextFactory $contextFactory;
}
