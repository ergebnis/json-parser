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

namespace Ergebnis\Json\Parser\Test\Unit\Traverser;

use Ergebnis\Json\Parser;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Traverser\EnterAction
 *
 * @uses \Ergebnis\Json\Parser\Node\NullNode
 */
final class EnterActionTest extends Framework\TestCase
{
    public function testKeepReturnsEnterAction(): void
    {
        $action = Parser\Traverser\EnterAction::keep();

        self::assertFalse($action->isRemove());
        self::assertFalse($action->isSkipChildren());
        self::assertNull($action->replacement());
        self::assertSame($action, Parser\Traverser\EnterAction::keep());
    }

    public function testRemoveReturnsEnterAction(): void
    {
        $action = Parser\Traverser\EnterAction::remove();

        self::assertTrue($action->isRemove());
        self::assertFalse($action->isSkipChildren());
        self::assertNull($action->replacement());
        self::assertSame($action, Parser\Traverser\EnterAction::remove());
    }

    public function testReplaceReturnsEnterAction(): void
    {
        $node = Parser\Node\NullNode::create();

        $action = Parser\Traverser\EnterAction::replace($node);

        self::assertFalse($action->isRemove());
        self::assertFalse($action->isSkipChildren());
        self::assertSame($node, $action->replacement());
    }

    public function testSkipChildrenReturnsEnterAction(): void
    {
        $action = Parser\Traverser\EnterAction::skipChildren();

        self::assertFalse($action->isRemove());
        self::assertTrue($action->isSkipChildren());
        self::assertNull($action->replacement());
        self::assertSame($action, Parser\Traverser\EnterAction::skipChildren());
    }
}
