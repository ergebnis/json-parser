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

final class SortingVisitor implements Traverser\Visitor
{
    public function enter(
        Node\Node $node,
        Traverser\Path $path
    ): Traverser\EnterAction {
        return Traverser\EnterAction::keep();
    }

    public function leave(
        Node\Node $node,
        Traverser\Path $path
    ): Traverser\LeaveAction {
        if (!$node instanceof Node\ObjectNode) {
            return Traverser\LeaveAction::keep();
        }

        $properties = $node->properties();
        $indexes = \array_keys($properties);

        \usort($indexes, static function (int $one, int $two) use ($properties): int {
            $comparison = \strcmp(
                $properties[$one]->name()->toString(),
                $properties[$two]->name()->toString(),
            );

            if (0 !== $comparison) {
                return $comparison;
            }

            return $one <=> $two;
        });

        $sortedProperties = [];

        foreach ($indexes as $index) {
            $sortedProperties[] = $properties[$index];
        }

        return Traverser\LeaveAction::replace(Node\ObjectNode::create(...$sortedProperties));
    }
}
