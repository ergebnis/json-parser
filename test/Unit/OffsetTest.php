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

use Ergebnis\Json\Parser\InvalidOffset;
use Ergebnis\Json\Parser\Offset;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Offset
 *
 * @uses \Ergebnis\Json\Parser\InvalidOffset
 */
final class OffsetTest extends Framework\TestCase
{
    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::lessThanZero
     */
    public function testFromIntThrowsInvalidOffsetWhenValueIsNegative(int $value): void
    {
        $this->expectException(InvalidOffset::class);

        Offset::fromInt($value);
    }

    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::greaterThanZero
     * @dataProvider \Ergebnis\DataProvider\IntProvider::zero
     */
    public function testFromIntReturnsOffsetWhenValueIsNotNegative(int $value): void
    {
        $offset = Offset::fromInt($value);

        self::assertSame($value, $offset->toInt());
    }
}
