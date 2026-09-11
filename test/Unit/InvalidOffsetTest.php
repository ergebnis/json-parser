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
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\InvalidOffset
 */
final class InvalidOffsetTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testLessThanZeroReturnsInvalidOffset(): void
    {
        $value = self::faker()->numberBetween(-100, -1);

        $exception = InvalidOffset::lessThanZero($value);

        $message = \sprintf(
            'Offset %d must not be negative.',
            $value,
        );

        self::assertSame($message, $exception->getMessage());
    }
}
