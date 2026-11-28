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

final class SortingVisitor implements Parser\Traverser\Visitor
{
    public function enter(
        Parser\Node\Node $node,
        Parser\Traverser\Path $path
    ): Parser\Traverser\EnterAction {
        return Parser\Traverser\EnterAction::keep();
    }

    public function leave(
        Parser\Node\Node $node,
        Parser\Traverser\Path $path
    ): Parser\Traverser\LeaveAction {
        if (!$node instanceof Parser\Node\ObjectNode) {
            return Parser\Traverser\LeaveAction::keep();
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

        return Parser\Traverser\LeaveAction::replace(Parser\Node\ObjectNode::create(...$sortedProperties));
    }
}
