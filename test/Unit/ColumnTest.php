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

use Ergebnis\Json\Parser\Column;
use Ergebnis\Json\Parser\InvalidColumn;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Column
 *
 * @uses \Ergebnis\Json\Parser\InvalidColumn
 */
final class ColumnTest extends Framework\TestCase
{
    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::lessThanZero
     * @dataProvider \Ergebnis\DataProvider\IntProvider::zero
     */
    public function testFromIntThrowsInvalidColumnWhenValueIsNotGreaterThanZero(int $value): void
    {
        $this->expectException(InvalidColumn::class);

        Column::fromInt($value);
    }

    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::greaterThanZero
     */
    public function testFromIntReturnsColumnWhenValueIsGreaterThanZero(int $value): void
    {
        $column = Column::fromInt($value);

        self::assertSame($value, $column->toInt());
    }
}
