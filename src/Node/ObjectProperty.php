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

namespace Ergebnis\Json\Parser\Node;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc8259#section-4
 */
final class ObjectProperty
{
    private StringNode $name;
    private Node $value;

    private function __construct(
        StringNode $name,
        Node $value
    ) {
        $this->name = $name;
        $this->value = $value;
    }

    public static function create(
        StringNode $name,
        Node $value
    ): self {
        return new self(
            $name,
            $value,
        );
    }

    public function name(): StringNode
    {
        return $this->name;
    }

    public function value(): Node
    {
        return $this->value;
    }
}
