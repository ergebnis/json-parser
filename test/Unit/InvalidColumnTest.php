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

use Ergebnis\Json\Parser\InvalidColumn;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\InvalidColumn
 */
final class InvalidColumnTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testNotGreaterThanZeroReturnsInvalidColumn(): void
    {
        $value = self::faker()->numberBetween(-100, 0);

        $exception = InvalidColumn::notGreaterThanZero($value);

        $message = \sprintf(
            'Column %d must be greater than 0.',
            $value,
        );

        self::assertSame($message, $exception->getMessage());
    }
}
