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

namespace Ergebnis\Json\Parser\Traverser;

use Ergebnis\Json\Parser;

interface Visitor
{
    public function enter(
        Parser\Node\Node $node,
        Path $path
    ): EnterAction;

    public function leave(
        Parser\Node\Node $node,
        Path $path
    ): LeaveAction;
}
