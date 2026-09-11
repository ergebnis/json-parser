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

final class NumberCanNotBeRepresented extends \RuntimeException implements Exception
{
    public static function asInt(NumberNode $node): self
    {
        return new self(\sprintf(
            '"%s" can not be represented as an int.',
            $node->raw(),
        ));
    }

    public static function asFloat(NumberNode $node): self
    {
        return new self(\sprintf(
            '"%s" can not be represented as a float.',
            $node->raw(),
        ));
    }
}
