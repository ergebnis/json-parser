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

use Ergebnis\Json\Parser\InvalidIndentSize;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\InvalidIndentSize
 */
final class InvalidIndentSizeTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testNotGreaterThanZeroReturnsInvalidIndentSize(): void
    {
        $value = self::faker()->numberBetween(-100, 0);

        $exception = InvalidIndentSize::notGreaterThanZero($value);

        $message = \sprintf(
            'Indent size %d must be greater than 0.',
            $value,
        );

        self::assertSame($message, $exception->getMessage());
    }
}
