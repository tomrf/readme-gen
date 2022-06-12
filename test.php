<?php

declare(strict_types=1);

use Tomrf\ReadmeGen\ReadmeGen;

require 'vendor/autoload.php';

$readmeGen = new ReadmeGen('.');
// $readmeGen = new ReadmeGen('/home/tom/projects/tom/tomrf-autowire');
// $readmeGen = new ReadmeGen('/home/tom/projects/tom/tomrf-autowire');
$readmeGen->writeMarkdown(STDOUT);
