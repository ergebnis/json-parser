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

use Ergebnis\Json\Parser\CyclicNodeDetected;
use Ergebnis\Json\Parser\Node;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\CyclicNodeDetected
 *
 * @uses \Ergebnis\Json\Parser\Node\ArrayNode
 */
final class CyclicNodeDetectedTest extends Framework\TestCase
{
    public function testFromNodeReturnsCyclicNodeDetected(): void
    {
        $node = Node\ArrayNode::create();

        $exception = CyclicNodeDetected::fromNode($node);

        $expected = \sprintf(
            'Node of class "%s" contains itself.',
            Node\ArrayNode::class,
        );

        self::assertSame($expected, $exception->getMessage());
    }
}
