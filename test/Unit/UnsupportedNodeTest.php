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

use Ergebnis\Json\Parser\Test;
use Ergebnis\Json\Parser\UnsupportedNode;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\UnsupportedNode
 */
final class UnsupportedNodeTest extends Framework\TestCase
{
    public function testFromNodeReturnsUnsupportedNode(): void
    {
        $node = new Test\Double\Node\CustomNode();

        $exception = UnsupportedNode::fromNode($node);

        $expected = \sprintf(
            'Node of class "%s" is not supported.',
            Test\Double\Node\CustomNode::class,
        );

        self::assertSame($expected, $exception->getMessage());
    }
}
