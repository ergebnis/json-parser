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

final class MaximumDepth
{
    private int $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function default(): self
    {
        return new self(512);
    }

    /**
     * @param int<1, 4096> $value
     *
     * @throws InvalidMaximumDepth
     */
    public static function fromInt(int $value): self
    {
        if (1 > $value) {
            throw InvalidMaximumDepth::notGreaterThanZero($value);
        }

        $limit = 4096;

        if ($limit < $value) {
            throw InvalidMaximumDepth::greaterThanLimit(
                $value,
                $limit,
            );
        }

        return new self($value);
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
