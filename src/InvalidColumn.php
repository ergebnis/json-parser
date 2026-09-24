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

final class InvalidColumn extends \InvalidArgumentException implements Exception
{
    public static function notGreaterThanZero(int $value): self
    {
        return new self(\sprintf(
            'Column %d must be greater than 0.',
            $value,
        ));
    }
}
