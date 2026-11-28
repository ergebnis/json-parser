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

namespace Ergebnis\Json\Parser\Test\Unit\Node;

use Ergebnis\Json\Parser;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Node\ElementDoesNotExist
 *
 * @uses \Ergebnis\Json\Parser\Index
 */
final class ElementDoesNotExistTest extends Framework\TestCase
{
    use Parser\Test\Util\Helper;

    public function testAtReturnsElementDoesNotExist(): void
    {
        $index = Parser\Index::fromInt(self::faker()->numberBetween(0, 100));

        $exception = Parser\Node\ElementDoesNotExist::at($index);

        $message = \sprintf(
            'Element at index %d does not exist.',
            $index->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
    }
}
