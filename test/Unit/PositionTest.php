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

use Ergebnis\Json\Parser;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Position
 *
 * @uses \Ergebnis\Json\Parser\Column
 * @uses \Ergebnis\Json\Parser\Line
 * @uses \Ergebnis\Json\Parser\Offset
 */
final class PositionTest extends Framework\TestCase
{
    use Parser\Test\Util\Helper;

    public function testCreateReturnsPosition(): void
    {
        $faker = self::faker();

        $offset = Parser\Offset::fromInt($faker->numberBetween(0, 100));
        $line = Parser\Line::fromInt($faker->numberBetween(1, 100));
        $column = Parser\Column::fromInt($faker->numberBetween(1, 100));

        $position = Parser\Position::create(
            $offset,
            $line,
            $column,
        );

        self::assertSame($offset, $position->offset());
        self::assertSame($line, $position->line());
        self::assertSame($column, $position->column());
    }
}
