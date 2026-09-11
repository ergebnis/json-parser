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

use Ergebnis\Json\Parser\Index;
use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Node\PropertyDoesNotExist
 *
 * @uses \Ergebnis\Json\Parser\Index
 */
final class PropertyDoesNotExistTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testAtReturnsPropertyDoesNotExist(): void
    {
        $index = Index::fromInt(self::faker()->numberBetween(0, 100));

        $exception = Node\PropertyDoesNotExist::at($index);

        $message = \sprintf(
            'Property at index %d does not exist.',
            $index->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
    }
}
