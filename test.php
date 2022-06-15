<?php

declare(strict_types=1);

require 'vendor/autoload.php';

$readmeGen = new \Tomrf\ReadmeGen\ReadmeGen('.'); // path to project directory
echo $readmeGen->generate(
    new Tomrf\ReadmeGen\Formatter\MarkdownFormatter(
        \phpDocumentor\Reflection\DocBlockFactory::createInstance(),
        new \phpDocumentor\Reflection\Types\ContextFactory(),
    ),
    'resources/template.md'
);
