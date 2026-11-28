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
 * @covers \Ergebnis\Json\Parser\Node\ObjectProperty
 *
 * @uses \Ergebnis\Json\Parser\Node\NullNode
 * @uses \Ergebnis\Json\Parser\Node\StringNode
 */
final class ObjectPropertyTest extends Framework\TestCase
{
    use Parser\Test\Util\Helper;

    public function testCreateReturnsObjectProperty(): void
    {
        $name = Parser\Node\StringNode::fromString(self::faker()->word());
        $value = Parser\Node\NullNode::create();

        $property = Parser\Node\ObjectProperty::create(
            $name,
            $value,
        );

        self::assertSame($name, $property->name());
        self::assertSame($value, $property->value());
    }
}
