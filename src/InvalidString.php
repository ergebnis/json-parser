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

final class InvalidString extends \InvalidArgumentException implements Exception
{
    public static function notValidJsonString(Raw $raw): self
    {
        return new self(\sprintf(
            '"%s" is not a valid JSON string.',
            $raw->toBytes()->toEscapedString(),
        ));
    }

    public static function notValidUtf8(string $value): self
    {
        return new self(\sprintf(
            '"%s" is not valid UTF-8.',
            Bytes::fromString($value)->toEscapedString(),
        ));
    }
}
