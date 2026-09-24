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

final class Bytes
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toEscapedString(): string
    {
        $escaped = '';
        $length = \strlen($this->value);

        for ($index = 0; $index < $length; ++$index) {
            $byte = \ord($this->value[$index]);

            if (
                0x20 > $byte
                || 0x7E < $byte
            ) {
                $escaped .= \sprintf(
                    '\x%02X',
                    $byte,
                );

                continue;
            }

            $escaped .= $this->value[$index];
        }

        return $escaped;
    }
}
