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

use Ergebnis\Json\Parser\Index;
use Ergebnis\Json\Parser\InvalidIndex;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Index
 *
 * @uses \Ergebnis\Json\Parser\InvalidIndex
 */
final class IndexTest extends Framework\TestCase
{
    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::lessThanZero
     */
    public function testFromIntThrowsInvalidIndexWhenValueIsNegative(int $value): void
    {
        $this->expectException(InvalidIndex::class);

        Index::fromInt($value);
    }

    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::greaterThanZero
     * @dataProvider \Ergebnis\DataProvider\IntProvider::zero
     */
    public function testFromIntReturnsIndexWhenValueIsNotNegative(int $value): void
    {
        $index = Index::fromInt($value);

        self::assertSame($value, $index->toInt());
    }
}
