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

final class IndentSize
{
    private int $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @param positive-int $value
     *
     * @throws InvalidIndentSize
     */
    public static function fromInt(int $value): self
    {
        if (1 > $value) {
            throw InvalidIndentSize::notGreaterThanZero($value);
        }

        return new self($value);
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
