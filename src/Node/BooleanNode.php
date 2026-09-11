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
 * @see https://datatracker.ietf.org/doc/html/rfc8259#section-3
 */
final class BooleanNode implements Node
{
    private bool $value;

    private function __construct(bool $value)
    {
        $this->value = $value;
    }

    public static function fromBool(bool $value): self
    {
        return new self($value);
    }

    public function toBool(): bool
    {
        return $this->value;
    }
}
