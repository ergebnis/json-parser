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

use Ergebnis\Json\Parser\InvalidMaximumDepth;
use Ergebnis\Json\Parser\MaximumDepth;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\MaximumDepth
 *
 * @uses \Ergebnis\Json\Parser\InvalidMaximumDepth
 */
final class MaximumDepthTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testDefaultReturnsMaximumDepth(): void
    {
        $maximumDepth = MaximumDepth::default();

        self::assertSame(512, $maximumDepth->toInt());
    }

    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::lessThanZero
     * @dataProvider \Ergebnis\DataProvider\IntProvider::zero
     */
    public function testFromIntThrowsInvalidMaximumDepthWhenValueIsNotGreaterThanZero(int $value): void
    {
        $this->expectException(InvalidMaximumDepth::class);
        $this->expectExceptionMessage(\sprintf(
            'Maximum depth %d must be greater than 0.',
            $value,
        ));

        MaximumDepth::fromInt($value);
    }

    /**
     * @dataProvider provideValueGreaterThan4096
     */
    public function testFromIntThrowsInvalidMaximumDepthWhenValueIsGreaterThan4096(int $value): void
    {
        $this->expectException(InvalidMaximumDepth::class);
        $this->expectExceptionMessage(\sprintf(
            'Maximum depth %d must not be greater than 4096.',
            $value,
        ));

        MaximumDepth::fromInt($value);
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function provideValueGreaterThan4096(): iterable
    {
        $values = [
            'one-greater-than-4096' => 4097,
            'greater-than-4096' => self::faker()->numberBetween(4098, \PHP_INT_MAX),
            'php-int-max' => \PHP_INT_MAX,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideValueGreaterThanZeroAndNotGreaterThan4096
     */
    public function testFromIntReturnsMaximumDepthWhenValueIsGreaterThanZeroAndNotGreaterThan4096(int $value): void
    {
        $maximumDepth = MaximumDepth::fromInt($value);

        self::assertSame($value, $maximumDepth->toInt());
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function provideValueGreaterThanZeroAndNotGreaterThan4096(): iterable
    {
        $values = [
            'one' => 1,
            'greater-than-one-and-less-than-4096' => self::faker()->numberBetween(2, 4095),
            'limit' => 4096,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }
}
