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
 * @covers \Ergebnis\Json\Parser\InvalidIndex
 */
final class InvalidIndexTest extends Framework\TestCase
{
    use Parser\Test\Util\Helper;

    public function testLessThanZeroReturnsInvalidIndex(): void
    {
        $index = self::faker()->numberBetween(-100, -1);

        $exception = Parser\InvalidIndex::lessThanZero($index);

        $message = \sprintf(
            'Index %d must not be negative.',
            $index,
        );

        self::assertSame($message, $exception->getMessage());
    }
}
