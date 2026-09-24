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

namespace Ergebnis\Json\Parser\Test\Double\Traverser;

use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Traverser;

final class ThrowingVisitor implements Traverser\Visitor
{
    private \Throwable $throwable;
    private bool $hasThrown = false;

    public function __construct(\Throwable $throwable)
    {
        $this->throwable = $throwable;
    }

    public function enter(
        Node\Node $node,
        Traverser\Path $path
    ): Traverser\EnterAction {
        if (
            !$this->hasThrown
            && $node instanceof Node\StringNode
        ) {
            $this->hasThrown = true;

            throw $this->throwable;
        }

        return Traverser\EnterAction::keep();
    }

    public function leave(
        Node\Node $node,
        Traverser\Path $path
    ): Traverser\LeaveAction {
        return Traverser\LeaveAction::keep();
    }
}
