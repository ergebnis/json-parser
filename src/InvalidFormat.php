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

final class InvalidFormat extends \InvalidArgumentException implements Exception
{
    public static function finalNewLineWithoutNewLine(): self
    {
        return new self('A final new line requires a new line.');
    }

    public static function indentWithoutNewLine(): self
    {
        return new self('An indent requires a new line.');
    }
}
