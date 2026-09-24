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

final class InvalidIndex extends \InvalidArgumentException implements Exception
{
    public static function lessThanZero(int $value): self
    {
        return new self(\sprintf(
            'Index %d must not be negative.',
            $value,
        ));
    }
}
