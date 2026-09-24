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

use Ergebnis\Json\Parser\InvalidLine;
use Ergebnis\Json\Parser\Line;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Line
 *
 * @uses \Ergebnis\Json\Parser\InvalidLine
 */
final class LineTest extends Framework\TestCase
{
    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::lessThanZero
     * @dataProvider \Ergebnis\DataProvider\IntProvider::zero
     */
    public function testFromIntThrowsInvalidLineWhenValueIsNotGreaterThanZero(int $value): void
    {
        $this->expectException(InvalidLine::class);

        Line::fromInt($value);
    }

    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::greaterThanZero
     */
    public function testFromIntReturnsLineWhenValueIsGreaterThanZero(int $value): void
    {
        $line = Line::fromInt($value);

        self::assertSame($value, $line->toInt());
    }
}
