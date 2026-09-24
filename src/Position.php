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

final class Position
{
    private Offset $offset;
    private Line $line;
    private Column $column;

    private function __construct(
        Offset $offset,
        Line $line,
        Column $column
    ) {
        $this->offset = $offset;
        $this->line = $line;
        $this->column = $column;
    }

    public static function create(
        Offset $offset,
        Line $line,
        Column $column
    ): self {
        return new self(
            $offset,
            $line,
            $column,
        );
    }

    public function offset(): Offset
    {
        return $this->offset;
    }

    public function line(): Line
    {
        return $this->line;
    }

    public function column(): Column
    {
        return $this->column;
    }
}
