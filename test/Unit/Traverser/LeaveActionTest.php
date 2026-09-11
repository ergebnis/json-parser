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

use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Traverser;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Traverser\LeaveAction
 *
 * @uses \Ergebnis\Json\Parser\Node\NullNode
 */
final class LeaveActionTest extends Framework\TestCase
{
    public function testKeepReturnsLeaveAction(): void
    {
        $action = Traverser\LeaveAction::keep();

        self::assertFalse($action->isRemove());
        self::assertNull($action->replacement());
        self::assertSame($action, Traverser\LeaveAction::keep());
    }

    public function testRemoveReturnsLeaveAction(): void
    {
        $action = Traverser\LeaveAction::remove();

        self::assertTrue($action->isRemove());
        self::assertNull($action->replacement());
        self::assertSame($action, Traverser\LeaveAction::remove());
    }

    public function testReplaceReturnsLeaveAction(): void
    {
        $node = Node\NullNode::create();

        $action = Traverser\LeaveAction::replace($node);

        self::assertFalse($action->isRemove());
        self::assertSame($node, $action->replacement());
    }
}
