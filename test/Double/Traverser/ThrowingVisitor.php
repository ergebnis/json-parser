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

use Ergebnis\Json\Parser;

final class ThrowingVisitor implements Parser\Traverser\Visitor
{
    private \Throwable $throwable;
    private bool $hasThrown = false;

    public function __construct(\Throwable $throwable)
    {
        $this->throwable = $throwable;
    }

    public function enter(
        Parser\Node\Node $node,
        Parser\Traverser\Path $path
    ): Parser\Traverser\EnterAction {
        if (
            !$this->hasThrown
            && $node instanceof Parser\Node\StringNode
        ) {
            $this->hasThrown = true;

            throw $this->throwable;
        }

        return Parser\Traverser\EnterAction::keep();
    }

    public function leave(
        Parser\Node\Node $node,
        Parser\Traverser\Path $path
    ): Parser\Traverser\LeaveAction {
        return Parser\Traverser\LeaveAction::keep();
    }
}
