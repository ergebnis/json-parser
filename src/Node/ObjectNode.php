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
 * @see https://datatracker.ietf.org/doc/html/rfc8259#section-4
 */
final class ObjectNode implements Node
{
    /**
     * @var list<ObjectProperty>
     */
    private array $properties;

    private function __construct(ObjectProperty ...$properties)
    {
        $this->properties = $properties;
    }

    public static function create(ObjectProperty ...$properties): self
    {
        return new self(...$properties);
    }

    /**
     * @return list<ObjectProperty>
     */
    public function properties(): array
    {
        return $this->properties;
    }

    public function count(): int
    {
        return \count($this->properties);
    }

    /**
     * @throws PropertyDoesNotExist
     */
    public function propertyAt(Index $index): ObjectProperty
    {
        if (!\array_key_exists($index->toInt(), $this->properties)) {
            throw PropertyDoesNotExist::at($index);
        }

        return $this->properties[$index->toInt()];
    }

    public function addProperty(ObjectProperty $property): void
    {
        $this->properties[] = $property;
    }

    /**
     * @throws PropertyDoesNotExist
     */
    public function insertPropertyAt(
        Index $index,
        ObjectProperty $property
    ): void {
        if (!\array_key_exists($index->toInt(), $this->properties)) {
            throw PropertyDoesNotExist::at($index);
        }

        \array_splice(
            $this->properties,
            $index->toInt(),
            0,
            [
                $property,
            ],
        );
    }

    /**
     * @throws PropertyDoesNotExist
     */
    public function removePropertyAt(Index $index): void
    {
        if (!\array_key_exists($index->toInt(), $this->properties)) {
            throw PropertyDoesNotExist::at($index);
        }

        \array_splice(
            $this->properties,
            $index->toInt(),
            1,
        );
    }

    /**
     * @throws PropertyDoesNotExist
     */
    public function replacePropertyAt(
        Index $index,
        ObjectProperty $property
    ): void {
        if (!\array_key_exists($index->toInt(), $this->properties)) {
            throw PropertyDoesNotExist::at($index);
        }

        $this->properties[$index->toInt()] = $property;
    }
}
