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

use Ergebnis\Json\Parser\Index;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc8259#section-5
 */
final class ArrayNode implements Node
{
    /**
     * @var list<Node>
     */
    private array $elements;

    private function __construct(Node ...$elements)
    {
        $this->elements = $elements;
    }

    public static function create(Node ...$elements): self
    {
        return new self(...$elements);
    }

    /**
     * @return list<Node>
     */
    public function elements(): array
    {
        return $this->elements;
    }

    public function count(): int
    {
        return \count($this->elements);
    }

    /**
     * @throws ElementDoesNotExist
     */
    public function elementAt(Index $index): Node
    {
        if (!\array_key_exists($index->toInt(), $this->elements)) {
            throw ElementDoesNotExist::at($index);
        }

        return $this->elements[$index->toInt()];
    }

    public function addElement(Node $element): void
    {
        $this->elements[] = $element;
    }

    /**
     * @throws ElementDoesNotExist
     */
    public function insertElementAt(
        Index $index,
        Node $element
    ): void {
        if (!\array_key_exists($index->toInt(), $this->elements)) {
            throw ElementDoesNotExist::at($index);
        }

        \array_splice(
            $this->elements,
            $index->toInt(),
            0,
            [
                $element,
            ],
        );
    }

    /**
     * @throws ElementDoesNotExist
     */
    public function removeElementAt(Index $index): void
    {
        if (!\array_key_exists($index->toInt(), $this->elements)) {
            throw ElementDoesNotExist::at($index);
        }

        \array_splice(
            $this->elements,
            $index->toInt(),
            1,
        );
    }

    /**
     * @throws ElementDoesNotExist
     */
    public function replaceElementAt(
        Index $index,
        Node $element
    ): void {
        if (!\array_key_exists($index->toInt(), $this->elements)) {
            throw ElementDoesNotExist::at($index);
        }

        $this->elements[$index->toInt()] = $element;
    }
}
