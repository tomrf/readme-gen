<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen;

use ReflectionClass;
use ReflectionMethod;

interface ReadmeFormatterInterface
{
    public function formatToc(array $structure): string;

    public function formatMethod(ReflectionMethod $reflection): string;

    public function formatClass(ReflectionClass $reflection): string;
}
