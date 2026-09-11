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

use Ergebnis\Json\Parser\Node;

final class EnterAction
{
    private static ?self $keep = null;
    private static ?self $remove = null;
    private static ?self $skipChildren = null;
    private string $type;
    private ?Node\Node $node = null;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function keep(): self
    {
        if (!self::$keep instanceof self) {
            self::$keep = new self('keep');
        }

        return self::$keep;
    }

    public static function remove(): self
    {
        if (!self::$remove instanceof self) {
            self::$remove = new self('remove');
        }

        return self::$remove;
    }

    public static function replace(Node\Node $node): self
    {
        $action = new self('replace');

        $action->node = $node;

        return $action;
    }

    public static function skipChildren(): self
    {
        if (!self::$skipChildren instanceof self) {
            self::$skipChildren = new self('skipChildren');
        }

        return self::$skipChildren;
    }

    /**
     * @internal
     */
    public function isRemove(): bool
    {
        return 'remove' === $this->type;
    }

    /**
     * @internal
     */
    public function isSkipChildren(): bool
    {
        return 'skipChildren' === $this->type;
    }

    /**
     * @internal
     */
    public function replacement(): ?Node\Node
    {
        return $this->node;
    }
}
