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

final class Index
{
    private int $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @param non-negative-int $value
     *
     * @throws InvalidIndex
     */
    public static function fromInt(int $value): self
    {
        if (0 > $value) {
            throw InvalidIndex::lessThanZero($value);
        }

        return new self($value);
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
