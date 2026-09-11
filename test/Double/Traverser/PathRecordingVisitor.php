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

final class PathRecordingVisitor implements Traverser\Visitor
{
    /**
     * @var list<Traverser\Path>
     */
    private array $paths = [];

    public function enter(
        Node\Node $node,
        Traverser\Path $path
    ): Traverser\EnterAction {
        $this->paths[] = $path;

        return Traverser\EnterAction::keep();
    }

    public function leave(
        Node\Node $node,
        Traverser\Path $path
    ): Traverser\LeaveAction {
        return Traverser\LeaveAction::keep();
    }

    /**
     * @return list<Traverser\Path>
     */
    public function paths(): array
    {
        return $this->paths;
    }
}
