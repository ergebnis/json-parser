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

use Ergebnis\Json\Parser;
use Ergebnis\Json\Pointer;

final class Path
{
    private self $parent;
    private ?Parser\Index $index = null;
    private ?Parser\Node\StringNode $name = null;
    private ?Pointer\JsonPointer $jsonPointer = null;

    private function __construct()
    {
    }

    public static function root(): self
    {
        $path = new self();

        $path->parent = $path;

        return $path;
    }

    public function element(Parser\Index $index): self
    {
        $path = new self();

        $path->parent = $this;
        $path->index = $index;

        return $path;
    }

    public function property(
        Parser\Index $index,
        Parser\Node\StringNode $name
    ): self {
        $path = new self();

        $path->parent = $this;
        $path->index = $index;
        $path->name = $name;

        return $path;
    }

    public function index(): ?Parser\Index
    {
        return $this->index;
    }

    public function name(): ?Parser\Node\StringNode
    {
        return $this->name;
    }

    /**
     * @see https://datatracker.ietf.org/doc/html/rfc6901
     */
    public function toJsonPointer(): Pointer\JsonPointer
    {
        if (!$this->jsonPointer instanceof Pointer\JsonPointer) {
            $this->jsonPointer = $this->createJsonPointer();
        }

        return $this->jsonPointer;
    }

    private function createJsonPointer(): Pointer\JsonPointer
    {
        if (!$this->index instanceof Parser\Index) {
            return Pointer\JsonPointer::document();
        }

        if ($this->name instanceof Parser\Node\StringNode) {
            return $this->parent->toJsonPointer()->append(Pointer\ReferenceToken::fromString($this->name->toString()));
        }

        return $this->parent->toJsonPointer()->append(Pointer\ReferenceToken::fromInt($this->index->toInt()));
    }
}
