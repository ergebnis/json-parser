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

final class InvalidNumber extends \InvalidArgumentException implements Exception
{
    public static function notValidJsonNumber(Raw $raw): self
    {
        return new self(\sprintf(
            '"%s" is not a valid JSON number.',
            $raw->toBytes()->toEscapedString(),
        ));
    }

    public static function notFinite(float $value): self
    {
        return new self(\sprintf(
            '%s can not be represented as a JSON number.',
            \var_export(
                $value,
                true,
            ),
        ));
    }
}
