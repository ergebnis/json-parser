<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/json-parser
 */

namespace Ergebnis\Json\Parser\Test\Unit;

use Ergebnis\Json\Parser\Bytes;
use Ergebnis\Json\Parser\Raw;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Raw
 *
 * @uses \Ergebnis\Json\Parser\Bytes
 */
final class RawTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testFromStringReturnsRaw(): void
    {
        $value = self::faker()->sentence();

        $raw = Raw::fromString($value);

        self::assertSame($value, $raw->toString());
    }

    public function testToBytesReturnsBytes(): void
    {
        $value = self::faker()->sentence();

        $raw = Raw::fromString($value);

        $expected = Bytes::fromString($value);

        self::assertEquals($expected, $raw->toBytes());
    }
}
