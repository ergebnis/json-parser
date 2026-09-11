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

final class SkippingVisitor implements Traverser\Visitor
{
    public function enter(
        Node\Node $node,
        Traverser\Path $path
    ): Traverser\EnterAction {
        return Traverser\EnterAction::skipChildren();
    }

    public function leave(
        Node\Node $node,
        Traverser\Path $path
    ): Traverser\LeaveAction {
        return Traverser\LeaveAction::keep();
    }
}
