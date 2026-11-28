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
 * @covers \Ergebnis\Json\Parser\Node\NumberCanNotBeRepresented
 *
 * @uses \Ergebnis\Json\Parser\Node\NumberNode
 */
final class NumberCanNotBeRepresentedTest extends Framework\TestCase
{
    use Parser\Test\Util\Helper;

    public function testAsIntReturnsNumberCanNotBeRepresented(): void
    {
        $node = Parser\Node\NumberNode::fromFloat(self::faker()->randomFloat());

        $exception = Parser\Node\NumberCanNotBeRepresented::asInt($node);

        $message = \sprintf(
            '"%s" can not be represented as an int.',
            $node->raw(),
        );

        self::assertSame($message, $exception->getMessage());
    }

    public function testAsFloatReturnsNumberCanNotBeRepresented(): void
    {
        $node = Parser\Node\NumberNode::fromFloat(self::faker()->randomFloat());

        $exception = Parser\Node\NumberCanNotBeRepresented::asFloat($node);

        $message = \sprintf(
            '"%s" can not be represented as a float.',
            $node->raw(),
        );

        self::assertSame($message, $exception->getMessage());
    }
}
