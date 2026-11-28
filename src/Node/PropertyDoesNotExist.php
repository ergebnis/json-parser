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

use Ergebnis\Json\Parser;

final class PropertyDoesNotExist extends \OutOfBoundsException implements Parser\Exception
{
    public static function at(Parser\Index $index): self
    {
        return new self(\sprintf(
            'Property at index %d does not exist.',
            $index->toInt(),
        ));
    }
}
