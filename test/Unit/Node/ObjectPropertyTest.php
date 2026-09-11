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

use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Node\ObjectProperty
 *
 * @uses \Ergebnis\Json\Parser\Node\NullNode
 * @uses \Ergebnis\Json\Parser\Node\StringNode
 */
final class ObjectPropertyTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testCreateReturnsObjectProperty(): void
    {
        $name = Node\StringNode::fromString(self::faker()->word());
        $value = Node\NullNode::create();

        $property = Node\ObjectProperty::create(
            $name,
            $value,
        );

        self::assertSame($name, $property->name());
        self::assertSame($value, $property->value());
    }
}
