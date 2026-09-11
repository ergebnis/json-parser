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
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Bytes
 */
final class BytesTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testFromStringReturnsBytes(): void
    {
        $value = self::faker()->sentence();

        $bytes = Bytes::fromString($value);

        self::assertSame($value, $bytes->toString());
    }

    public function testToEscapedStringReturnsPrintableAsciiUnchanged(): void
    {
        $value = \implode('', \array_map('\chr', \range(0x20, 0x7E)));

        $bytes = Bytes::fromString($value);

        self::assertSame($value, $bytes->toEscapedString());
    }

    /**
     * @dataProvider provideByteOutsidePrintableAscii
     */
    public function testToEscapedStringReturnsHexadecimalEscapeForByteOutsidePrintableAscii(int $byte): void
    {
        $bytes = Bytes::fromString('a' . \chr($byte) . 'b');

        $expected = \sprintf(
            'a\x%02Xb',
            $byte,
        );

        self::assertSame($expected, $bytes->toEscapedString());
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function provideByteOutsidePrintableAscii(): iterable
    {
        $values = [
            'delete' => 0x7F,
            'highest-byte' => 0xFF,
            'nul' => 0x00,
            'unit-separator' => 0x1F,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }
}
