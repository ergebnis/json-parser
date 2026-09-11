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

namespace Ergebnis\Json\Parser\Traverser;

use Ergebnis\Json\Parser\Exception;

final class RootNodeCanNotBeRemoved extends \RuntimeException implements Exception
{
    public static function by(Visitor $visitor): self
    {
        return new self(\sprintf(
            'Visitor of class "%s" removed the root node, which can not be removed.',
            \get_class($visitor),
        ));
    }
}
