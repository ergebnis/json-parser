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
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\InvalidLine
 */
final class InvalidLineTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testNotGreaterThanZeroReturnsInvalidLine(): void
    {
        $value = self::faker()->numberBetween(-100, 0);

        $exception = InvalidLine::notGreaterThanZero($value);

        $message = \sprintf(
            'Line %d must be greater than 0.',
            $value,
        );

        self::assertSame($message, $exception->getMessage());
    }
}
