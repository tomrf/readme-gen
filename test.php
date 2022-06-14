<?php

declare(strict_types=1);

use phpDocumentor\Reflection\Types\ContextFactory;
use Tomrf\ReadmeGen\MarkdownFormatter;
use Tomrf\ReadmeGen\ReadmeGen;

require 'vendor/autoload.php';

$readmeGen = new ReadmeGen('/home/tom/projects/tom/tomrf-autowire');
$readme = $readmeGen->generate(
    new MarkdownFormatter(
        \phpDocumentor\Reflection\DocBlockFactory::createInstance(),
        new ContextFactory(),
    ),
    'resources/template.md'
);

echo $readme;
