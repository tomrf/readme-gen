<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen\Test;

use PHPUnit\Framework\TestCase;
use Tomrf\ReadmeGen\ReadmeGen;

/**
 * @internal
 * @covers \Tomrf\ReadmeGen\Example
 */
final class ReadmeGenTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // ...
    }

    public function testExampleIsInstanceOfExample(): void
    {
        static::assertInstanceOf(ReadmeGen::class, new ReadmeGen());
    }

    public function testHello(): void
    {
        static::assertSame('Hello, world.', (new ReadmeGen())->hello());
    }
}
