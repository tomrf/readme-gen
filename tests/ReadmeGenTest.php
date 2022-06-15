<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen\Test;

use PHPUnit\Framework\TestCase;
use Tomrf\ReadmeGen\ReadmeGen;

/**
 * @internal
 * @covers \Tomrf\ReadmeGen\ReadmeGen
 */
final class ReadmeGenTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // ...
    }

    public function testNewReadmeGenIsInstanceOfReadmeGen(): void
    {
        static::assertInstanceOf(ReadmeGen::class, new ReadmeGen('.'));
    }
}
