<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen;

use ReflectionClass;
use ReflectionMethod;

interface ReadmeFormatterInterface
{
    public function formatMethod(ReflectionMethod $reflection): string;

    public function formatClass(ReflectionClass $reflection): string;
}
