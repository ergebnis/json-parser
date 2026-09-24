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

namespace Ergebnis\Json\Parser\Node;

use Ergebnis\Json\Parser\Exception;
use Ergebnis\Json\Parser\Index;

final class ElementDoesNotExist extends \OutOfBoundsException implements Exception
{
    public static function at(Index $index): self
    {
        return new self(\sprintf(
            'Element at index %d does not exist.',
            $index->toInt(),
        ));
    }
}
