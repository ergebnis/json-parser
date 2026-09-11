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

use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Node\NullNode
 */
final class NullNodeTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testCreateReturnsNullNode(): void
    {
        $one = Node\NullNode::create();
        $two = Node\NullNode::create();

        self::assertNotSame($one, $two);
    }
}
