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
 * @covers \Ergebnis\Json\Parser\Node\BooleanNode
 */
final class BooleanNodeTest extends Framework\TestCase
{
    use Parser\Test\Util\Helper;

    /**
     * @dataProvider provideValue
     */
    public function testFromBoolReturnsBooleanNode(bool $value): void
    {
        $node = Parser\Node\BooleanNode::fromBool($value);

        self::assertSame($value, $node->toBool());
    }

    /**
     * @return \Generator<string, array{0: bool}>
     */
    public static function provideValue(): iterable
    {
        $values = [
            'false' => false,
            'true' => true,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }
}
