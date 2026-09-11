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

namespace Ergebnis\Json\Parser\Test\Unit\Traverser;

use Ergebnis\Json\Parser\Index;
use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Raw;
use Ergebnis\Json\Parser\Test;
use Ergebnis\Json\Parser\Traverser;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Traverser\Path
 *
 * @uses \Ergebnis\Json\Parser\Index
 * @uses \Ergebnis\Json\Parser\Node\StringNode
 * @uses \Ergebnis\Json\Parser\Raw
 */
final class PathTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testRootReturnsPath(): void
    {
        $path = Traverser\Path::root();

        self::assertNull($path->index());
        self::assertNull($path->name());
        self::assertSame('', $path->toJsonPointer()->toJsonString());
    }

    public function testElementReturnsPath(): void
    {
        $index = Index::fromInt(self::faker()->numberBetween(0, 100));

        $parent = Traverser\Path::root();

        $path = $parent->element($index);

        self::assertSame($index, $path->index());
        self::assertNull($path->name());
        self::assertSame('/' . $index->toInt(), $path->toJsonPointer()->toJsonString());
    }

    public function testPropertyReturnsPath(): void
    {
        $index = Index::fromInt(self::faker()->numberBetween(0, 100));
        $name = Node\StringNode::fromRaw(Raw::fromString('"a\/b~c"'));

        $parent = Traverser\Path::root();

        $path = $parent->property(
            $index,
            $name,
        );

        self::assertSame($index, $path->index());
        self::assertSame($name, $path->name());
        self::assertSame('/a~1b~0c', $path->toJsonPointer()->toJsonString());
    }

    public function testToJsonPointerReturnsJsonPointerOfNestedPath(): void
    {
        $faker = self::faker();

        $outerIndex = Index::fromInt($faker->numberBetween(0, 100));
        $outerName = Node\StringNode::fromString($faker->word());
        $elementIndex = Index::fromInt($faker->numberBetween(0, 100));
        $innerIndex = Index::fromInt($faker->numberBetween(0, 100));
        $innerName = Node\StringNode::fromString($faker->word());

        $path = Traverser\Path::root()
            ->property(
                $outerIndex,
                $outerName,
            )
            ->element($elementIndex)
            ->property(
                $innerIndex,
                $innerName,
            );

        $expected = '/' . $outerName->toString() . '/' . $elementIndex->toInt() . '/' . $innerName->toString();

        self::assertSame($expected, $path->toJsonPointer()->toJsonString());
    }

    public function testToJsonPointerReturnsSameJsonPointerWhenCalledAgain(): void
    {
        $index = Index::fromInt(self::faker()->numberBetween(0, 100));

        $path = Traverser\Path::root()->element($index);

        $jsonPointer = $path->toJsonPointer();

        self::assertSame($jsonPointer, $path->toJsonPointer());
    }
}
