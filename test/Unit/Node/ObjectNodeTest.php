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

namespace Ergebnis\Json\Parser\Test\Unit\Node;

use Ergebnis\Json\Parser\Index;
use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Node\ObjectNode
 *
 * @uses \Ergebnis\Json\Parser\Index
 * @uses \Ergebnis\Json\Parser\Node\NullNode
 * @uses \Ergebnis\Json\Parser\Node\ObjectProperty
 * @uses \Ergebnis\Json\Parser\Node\PropertyDoesNotExist
 * @uses \Ergebnis\Json\Parser\Node\StringNode
 */
final class ObjectNodeTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testCreateReturnsObjectNodeWithoutProperties(): void
    {
        $node = Node\ObjectNode::create();

        self::assertSame([], $node->properties());
    }

    public function testCreateReturnsObjectNodeWhenPropertiesHaveUniqueNames(): void
    {
        $faker = self::faker()->unique();

        $properties = \array_map(static function () use ($faker): Node\ObjectProperty {
            return Node\ObjectProperty::create(
                Node\StringNode::fromString($faker->word()),
                Node\StringNode::fromString($faker->sentence()),
            );
        }, \range(0, 4));

        $node = Node\ObjectNode::create(...$properties);

        self::assertSame($properties, $node->properties());
    }

    public function testCreateReturnsObjectNodeWhenPropertiesHaveDuplicateNames(): void
    {
        $faker = self::faker()->unique();

        $properties = \array_map(static function () use ($faker): Node\ObjectProperty {
            return Node\ObjectProperty::create(
                Node\StringNode::fromString('foo'),
                Node\StringNode::fromString($faker->sentence()),
            );
        }, \range(0, 4));

        $node = Node\ObjectNode::create(...$properties);

        self::assertSame($properties, $node->properties());
    }

    public function testCountReturnsCountWhenObjectNodeHasNoProperties(): void
    {
        $node = Node\ObjectNode::create();

        self::assertSame(0, $node->count());
    }

    public function testCountReturnsCountWhenObjectNodeHasProperties(): void
    {
        $faker = self::faker();

        $count = $faker->numberBetween(1, 10);

        $properties = \array_map(static function () use ($faker): Node\ObjectProperty {
            return Node\ObjectProperty::create(
                Node\StringNode::fromString($faker->word()),
                Node\StringNode::fromString($faker->sentence()),
            );
        }, \range(1, $count));

        $node = Node\ObjectNode::create(...$properties);

        self::assertSame($count, $node->count());
    }

    public function testPropertyAtThrowsPropertyDoesNotExistWhenPropertyDoesNotExistAtIndex(): void
    {
        $index = Index::fromInt(0);

        $node = Node\ObjectNode::create();

        $this->expectException(Node\PropertyDoesNotExist::class);

        $node->propertyAt($index);
    }

    public function testPropertyAtReturnsPropertyAtIndex(): void
    {
        $faker = self::faker();

        $index = Index::fromInt(0);
        $property = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $node = Node\ObjectNode::create($property);

        self::assertSame($property, $node->propertyAt($index));
    }

    public function testAddPropertyAppendsProperty(): void
    {
        $faker = self::faker();

        $one = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $two = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $node = Node\ObjectNode::create();

        $node->addProperty($one);
        $node->addProperty($two);

        $expected = [
            $one,
            $two,
        ];

        self::assertSame($expected, $node->properties());
    }

    public function testInsertPropertyAtThrowsPropertyDoesNotExistWhenPropertyDoesNotExistAtIndex(): void
    {
        $faker = self::faker();

        $index = Index::fromInt(0);
        $property = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $node = Node\ObjectNode::create();

        $this->expectException(Node\PropertyDoesNotExist::class);

        $node->insertPropertyAt(
            $index,
            $property,
        );
    }

    public function testInsertPropertyAtInsertsPropertyBeforePropertyAtIndex(): void
    {
        $faker = self::faker();

        $index = Index::fromInt(1);
        $property = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $one = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $two = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $three = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $node = Node\ObjectNode::create(
            $one,
            $two,
            $three,
        );

        $node->insertPropertyAt(
            $index,
            $property,
        );

        $expected = [
            $one,
            $property,
            $two,
            $three,
        ];

        self::assertSame($expected, $node->properties());
    }

    public function testRemovePropertyAtThrowsPropertyDoesNotExistWhenPropertyDoesNotExistAtIndex(): void
    {
        $index = Index::fromInt(0);

        $node = Node\ObjectNode::create();

        $this->expectException(Node\PropertyDoesNotExist::class);

        $node->removePropertyAt($index);
    }

    public function testRemovePropertyAtRemovesAndReindexes(): void
    {
        $faker = self::faker();

        $one = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $two = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $three = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $node = Node\ObjectNode::create(
            $one,
            $two,
            $three,
        );

        $node->removePropertyAt(Index::fromInt(1));

        $expected = [
            $one,
            $three,
        ];

        self::assertSame($expected, $node->properties());

        self::assertSame($one, $node->propertyAt(Index::fromInt(0)));
        self::assertSame($three, $node->propertyAt(Index::fromInt(1)));
    }

    public function testReplacePropertyAtThrowsPropertyDoesNotExistWhenPropertyDoesNotExistAtIndex(): void
    {
        $faker = self::faker();

        $index = Index::fromInt(0);
        $property = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $node = Node\ObjectNode::create();

        $this->expectException(Node\PropertyDoesNotExist::class);

        $node->replacePropertyAt(
            $index,
            $property,
        );
    }

    public function testReplacePropertyAtReplacesPropertyAtIndex(): void
    {
        $faker = self::faker();

        $index = Index::fromInt(2);
        $property = Node\ObjectProperty::create(
            Node\StringNode::fromString($faker->word()),
            Node\StringNode::fromString($faker->sentence()),
        );

        $properties = \array_map(static function () use ($faker): Node\ObjectProperty {
            return Node\ObjectProperty::create(
                Node\StringNode::fromString('foo'),
                Node\StringNode::fromString($faker->sentence()),
            );
        }, \range(0, 4));

        $node = Node\ObjectNode::create(...$properties);

        $node->replacePropertyAt(
            $index,
            $property,
        );

        self::assertSame($property, $node->propertyAt($index));
    }
}
