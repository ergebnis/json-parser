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

use Ergebnis\Json\Parser\IndentSize;
use Ergebnis\Json\Parser\InvalidIndentSize;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\IndentSize
 *
 * @uses \Ergebnis\Json\Parser\InvalidIndentSize
 */
final class IndentSizeTest extends Framework\TestCase
{
    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::lessThanZero
     * @dataProvider \Ergebnis\DataProvider\IntProvider::zero
     */
    public function testFromIntThrowsInvalidIndentSizeWhenValueIsNotGreaterThanZero(int $value): void
    {
        $this->expectException(InvalidIndentSize::class);

        IndentSize::fromInt($value);
    }

    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::greaterThanZero
     */
    public function testFromIntReturnsIndentSizeWhenValueIsGreaterThanZero(int $value): void
    {
        $indentSize = IndentSize::fromInt($value);

        self::assertSame($value, $indentSize->toInt());
    }
}
