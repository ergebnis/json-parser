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

final class FinalNewLine
{
    private bool $value;

    private function __construct(bool $value)
    {
        $this->value = $value;
    }

    public static function none(): self
    {
        return new self(false);
    }

    public static function present(): self
    {
        return new self(true);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
