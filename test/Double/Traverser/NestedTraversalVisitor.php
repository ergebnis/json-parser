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

final class NestedTraversalVisitor implements Parser\Traverser\Visitor
{
    private string $jsonPointer;
    private Parser\Node\Node $root;
    private ?Parser\Traverser\Traverser $traverser = null;
    private bool $hasTraversedAgain = false;

    public function __construct(
        string $jsonPointer,
        Parser\Node\Node $root
    ) {
        $this->jsonPointer = $jsonPointer;
        $this->root = $root;
    }

    public function setTraverser(Parser\Traverser\Traverser $traverser): void
    {
        $this->traverser = $traverser;
    }

    public function hasTraversedAgain(): bool
    {
        return $this->hasTraversedAgain;
    }

    public function enter(
        Parser\Node\Node $node,
        Parser\Traverser\Path $path
    ): Parser\Traverser\EnterAction {
        if (
            !$this->hasTraversedAgain
            && $path->toJsonPointer()->toJsonString() === $this->jsonPointer
            && $this->traverser instanceof Parser\Traverser\Traverser
        ) {
            $this->hasTraversedAgain = true;

            $this->traverser->traverse($this->root);
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
