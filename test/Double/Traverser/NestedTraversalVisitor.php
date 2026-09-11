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

final class NestedTraversalVisitor implements Traverser\Visitor
{
    private string $jsonPointer;
    private Node\Node $root;
    private ?Traverser\Traverser $traverser = null;
    private bool $hasTraversedAgain = false;

    public function __construct(
        string $jsonPointer,
        Node\Node $root
    ) {
        $this->jsonPointer = $jsonPointer;
        $this->root = $root;
    }

    public function setTraverser(Traverser\Traverser $traverser): void
    {
        $this->traverser = $traverser;
    }

    public function hasTraversedAgain(): bool
    {
        return $this->hasTraversedAgain;
    }

    public function enter(
        Node\Node $node,
        Traverser\Path $path
    ): Traverser\EnterAction {
        if (
            !$this->hasTraversedAgain
            && $path->toJsonPointer()->toJsonString() === $this->jsonPointer
            && $this->traverser instanceof Traverser\Traverser
        ) {
            $this->hasTraversedAgain = true;

            $this->traverser->traverse($this->root);
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
