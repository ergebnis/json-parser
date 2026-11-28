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
 * @covers \Ergebnis\Json\Parser\Node\ArrayNode
 *
 * @uses \Ergebnis\Json\Parser\Index
 * @uses \Ergebnis\Json\Parser\Node\ElementDoesNotExist
 * @uses \Ergebnis\Json\Parser\Node\NullNode
 * @uses \Ergebnis\Json\Parser\Node\StringNode
 */
final class ArrayNodeTest extends Framework\TestCase
{
    use Parser\Test\Util\Helper;

    public function testCreateReturnsArrayNodeWithoutElements(): void
    {
        $node = Parser\Node\ArrayNode::create();

        self::assertSame([], $node->elements());
    }

    public function testCreateReturnsArrayNodeWithElements(): void
    {
        $faker = self::faker();

        $elements = \array_map(static function () use ($faker): Parser\Node\Node {
            return Parser\Node\StringNode::fromString($faker->sentence());
        }, \range(0, 4));

        $node = Parser\Node\ArrayNode::create(...$elements);

        self::assertSame($elements, $node->elements());
    }

    public function testCountReturnsCountWhenArrayNodeHasNoElements(): void
    {
        $node = Parser\Node\ArrayNode::create();

        self::assertSame(0, $node->count());
    }

    public function testCountReturnsCountWhenArrayNodeHasElements(): void
    {
        $faker = self::faker();

        $count = $faker->numberBetween(1, 10);

        $elements = \array_map(static function () use ($faker): Parser\Node\Node {
            return Parser\Node\StringNode::fromString($faker->sentence());
        }, \range(1, $count));

        $node = Parser\Node\ArrayNode::create(...$elements);

        self::assertSame($count, $node->count());
    }

    public function testElementAtThrowsElementDoesNotExistWhenElementDoesNotExistAtIndex(): void
    {
        $index = Parser\Index::fromInt(0);

        $node = Parser\Node\ArrayNode::create();

        $this->expectException(Parser\Node\ElementDoesNotExist::class);

        $node->elementAt($index);
    }

    public function testElementAtReturnsElementAtIndex(): void
    {
        $index = Parser\Index::fromInt(0);
        $element = Parser\Node\NullNode::create();

        $node = Parser\Node\ArrayNode::create($element);

        self::assertSame($element, $node->elementAt($index));
    }

    public function testAddElementAppendsElement(): void
    {
        $one = Parser\Node\NullNode::create();
        $two = Parser\Node\NullNode::create();

        $node = Parser\Node\ArrayNode::create();

        $node->addElement($one);
        $node->addElement($two);

        $expected = [
            $one,
            $two,
        ];

        self::assertSame($expected, $node->elements());
    }

    public function testInsertElementAtThrowsElementDoesNotExistWhenElementDoesNotExistAtIndex(): void
    {
        $index = Parser\Index::fromInt(0);
        $element = Parser\Node\NullNode::create();

        $node = Parser\Node\ArrayNode::create();

        $this->expectException(Parser\Node\ElementDoesNotExist::class);

        $node->insertElementAt(
            $index,
            $element,
        );
    }

    public function testInsertElementAtInsertsElementBeforeElementAtIndex(): void
    {
        $faker = self::faker();

        $index = Parser\Index::fromInt(1);
        $element = Parser\Node\StringNode::fromString($faker->sentence());

        $one = Parser\Node\StringNode::fromString($faker->sentence());
        $two = Parser\Node\StringNode::fromString($faker->sentence());
        $three = Parser\Node\StringNode::fromString($faker->sentence());

        $node = Parser\Node\ArrayNode::create(
            $one,
            $two,
            $three,
        );

        $node->insertElementAt(
            $index,
            $element,
        );

        $expected = [
            $one,
            $element,
            $two,
            $three,
        ];

        self::assertSame($expected, $node->elements());
    }

    public function testRemoveElementAtThrowsElementDoesNotExistWhenElementDoesNotExistAtIndex(): void
    {
        $index = Parser\Index::fromInt(0);

        $node = Parser\Node\ArrayNode::create();

        $this->expectException(Parser\Node\ElementDoesNotExist::class);

        $node->removeElementAt($index);
    }

    public function testRemoveElementAtRemovesAndReindexes(): void
    {
        $faker = self::faker();

        $one = Parser\Node\StringNode::fromString($faker->sentence());
        $two = Parser\Node\StringNode::fromString($faker->sentence());
        $three = Parser\Node\StringNode::fromString($faker->sentence());

        $node = Parser\Node\ArrayNode::create(
            $one,
            $two,
            $three,
        );

        $node->removeElementAt(Parser\Index::fromInt(1));

        $expected = [
            $one,
            $three,
        ];

        self::assertSame($expected, $node->elements());

        self::assertSame($one, $node->elementAt(Parser\Index::fromInt(0)));
        self::assertSame($three, $node->elementAt(Parser\Index::fromInt(1)));
    }

    public function testReplaceElementAtThrowsElementDoesNotExistWhenElementDoesNotExistAtIndex(): void
    {
        $index = Parser\Index::fromInt(0);
        $element = Parser\Node\NullNode::create();

        $node = Parser\Node\ArrayNode::create();

        $this->expectException(Parser\Node\ElementDoesNotExist::class);

        $node->replaceElementAt(
            $index,
            $element,
        );
    }

    public function testReplaceElementAtReplacesElementAtIndex(): void
    {
        $faker = self::faker();

        $index = Parser\Index::fromInt(2);
        $element = Parser\Node\StringNode::fromString($faker->sentence());

        $elements = \array_map(static function () use ($faker): Parser\Node\Node {
            return Parser\Node\StringNode::fromString($faker->sentence());
        }, \range(0, 4));

        $node = Parser\Node\ArrayNode::create(...$elements);

        $node->replaceElementAt(
            $index,
            $element,
        );

        self::assertSame($element, $node->elementAt($index));
    }
}
