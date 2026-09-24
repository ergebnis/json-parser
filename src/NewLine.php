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

final class NewLine
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function none(): self
    {
        return new self('');
    }

    public static function lf(): self
    {
        return new self("\n");
    }

    public static function crLf(): self
    {
        return new self("\r\n");
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
