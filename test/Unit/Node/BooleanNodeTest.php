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
 * @covers \Ergebnis\Json\Parser\Node\BooleanNode
 */
final class BooleanNodeTest extends Framework\TestCase
{
    use Test\Util\Helper;

    /**
     * @dataProvider provideValue
     */
    public function testFromBoolReturnsBooleanNode(bool $value): void
    {
        $node = Node\BooleanNode::fromBool($value);

        self::assertSame($value, $node->toBool());
    }

    /**
     * @return \Generator<string, array{0: bool}>
     */
    public static function provideValue(): iterable
    {
        $values = [
            'true' => true,
            'false' => false,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }
}
