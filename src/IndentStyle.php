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

final class IndentStyle
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function space(): self
    {
        return new self(' ');
    }

    public static function tab(): self
    {
        return new self("\t");
    }

    public function toString(): string
    {
        return $this->value;
    }
}
