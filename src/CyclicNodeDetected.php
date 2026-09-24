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

namespace Ergebnis\Json\Parser;

final class CyclicNodeDetected extends \RuntimeException implements Exception
{
    public static function fromNode(Node\Node $node): self
    {
        return new self(\sprintf(
            'Node of class "%s" contains itself.',
            \get_class($node),
        ));
    }
}
