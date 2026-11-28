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

use Ergebnis\Json\Parser;
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
    use Parser\Test\Util\Helper;

    public function testCreateReturnsObjectNodeWithoutProperties(): void
    {
        $node = Parser\Node\ObjectNode::create();

        self::assertSame([], $node->properties());
    }

    public function testCreateReturnsObjectNodeWhenPropertiesHaveUniqueNames(): void
    {
        $faker = self::faker()->unique();

        $properties = \array_map(static function () use ($faker): Parser\Node\ObjectProperty {
            return Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromString($faker->word()),
                Parser\Node\StringNode::fromString($faker->sentence()),
            );
        }, \range(0, 4));

        $node = Parser\Node\ObjectNode::create(...$properties);

        self::assertSame($properties, $node->properties());
    }

    public function testCreateReturnsObjectNodeWhenPropertiesHaveDuplicateNames(): void
    {
        $faker = self::faker()->unique();

        $properties = \array_map(static function () use ($faker): Parser\Node\ObjectProperty {
            return Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromString('foo'),
                Parser\Node\StringNode::fromString($faker->sentence()),
            );
        }, \range(0, 4));

        $node = Parser\Node\ObjectNode::create(...$properties);

        self::assertSame($properties, $node->properties());
    }

    public function testCountReturnsCountWhenObjectNodeHasNoProperties(): void
    {
        $node = Parser\Node\ObjectNode::create();

        self::assertSame(0, $node->count());
    }

    public function testCountReturnsCountWhenObjectNodeHasProperties(): void
    {
        $faker = self::faker();

        $count = $faker->numberBetween(1, 10);

        $properties = \array_map(static function () use ($faker): Parser\Node\ObjectProperty {
            return Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromString($faker->word()),
                Parser\Node\StringNode::fromString($faker->sentence()),
            );
        }, \range(1, $count));

        $node = Parser\Node\ObjectNode::create(...$properties);

        self::assertSame($count, $node->count());
    }

    public function testPropertyAtThrowsPropertyDoesNotExistWhenPropertyDoesNotExistAtIndex(): void
    {
        $index = Parser\Index::fromInt(0);

        $node = Parser\Node\ObjectNode::create();

        $this->expectException(Parser\Node\PropertyDoesNotExist::class);

        $node->propertyAt($index);
    }

    public function testPropertyAtReturnsPropertyAtIndex(): void
    {
        $faker = self::faker();

        $index = Parser\Index::fromInt(0);
        $property = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $node = Parser\Node\ObjectNode::create($property);

        self::assertSame($property, $node->propertyAt($index));
    }

    public function testAddPropertyAppendsProperty(): void
    {
        $faker = self::faker();

        $one = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $two = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $node = Parser\Node\ObjectNode::create();

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

        $index = Parser\Index::fromInt(0);
        $property = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $node = Parser\Node\ObjectNode::create();

        $this->expectException(Parser\Node\PropertyDoesNotExist::class);

        $node->insertPropertyAt(
            $index,
            $property,
        );
    }

    public function testInsertPropertyAtInsertsPropertyBeforePropertyAtIndex(): void
    {
        $faker = self::faker();

        $index = Parser\Index::fromInt(1);
        $property = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $one = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $two = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $three = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $node = Parser\Node\ObjectNode::create(
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
        $index = Parser\Index::fromInt(0);

        $node = Parser\Node\ObjectNode::create();

        $this->expectException(Parser\Node\PropertyDoesNotExist::class);

        $node->removePropertyAt($index);
    }

    public function testRemovePropertyAtRemovesAndReindexes(): void
    {
        $faker = self::faker();

        $one = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $two = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $three = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $node = Parser\Node\ObjectNode::create(
            $one,
            $two,
            $three,
        );

        $node->removePropertyAt(Parser\Index::fromInt(1));

        $expected = [
            $one,
            $three,
        ];

        self::assertSame($expected, $node->properties());

        self::assertSame($one, $node->propertyAt(Parser\Index::fromInt(0)));
        self::assertSame($three, $node->propertyAt(Parser\Index::fromInt(1)));
    }

    public function testReplacePropertyAtThrowsPropertyDoesNotExistWhenPropertyDoesNotExistAtIndex(): void
    {
        $faker = self::faker();

        $index = Parser\Index::fromInt(0);
        $property = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $node = Parser\Node\ObjectNode::create();

        $this->expectException(Parser\Node\PropertyDoesNotExist::class);

        $node->replacePropertyAt(
            $index,
            $property,
        );
    }

    public function testReplacePropertyAtReplacesPropertyAtIndex(): void
    {
        $faker = self::faker();

        $index = Parser\Index::fromInt(2);
        $property = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString($faker->word()),
            Parser\Node\StringNode::fromString($faker->sentence()),
        );

        $properties = \array_map(static function () use ($faker): Parser\Node\ObjectProperty {
            return Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromString('foo'),
                Parser\Node\StringNode::fromString($faker->sentence()),
            );
        }, \range(0, 4));

        $node = Parser\Node\ObjectNode::create(...$properties);

        $node->replacePropertyAt(
            $index,
            $property,
        );

        self::assertSame($property, $node->propertyAt($index));
    }
}
