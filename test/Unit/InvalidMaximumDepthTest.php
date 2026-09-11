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
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\InvalidMaximumDepth
 */
final class InvalidMaximumDepthTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testNotGreaterThanZeroReturnsInvalidMaximumDepth(): void
    {
        $value = self::faker()->numberBetween(-100, 0);

        $exception = InvalidMaximumDepth::notGreaterThanZero($value);

        $message = \sprintf(
            'Maximum depth %d must be greater than 0.',
            $value,
        );

        self::assertSame($message, $exception->getMessage());
    }

    public function testGreaterThanLimitReturnsInvalidMaximumDepth(): void
    {
        $faker = self::faker();

        $value = $faker->numberBetween(1, 100);
        $limit = $faker->numberBetween(101, 200);

        $exception = InvalidMaximumDepth::greaterThanLimit(
            $value,
            $limit,
        );

        $message = \sprintf(
            'Maximum depth %d must not be greater than %d.',
            $value,
            $limit,
        );

        self::assertSame($message, $exception->getMessage());
    }
}
