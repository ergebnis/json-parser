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

namespace Ergebnis\Json\Parser\Test\Unit;

use Ergebnis\Json\Parser\Indent;
use Ergebnis\Json\Parser\IndentSize;
use Ergebnis\Json\Parser\IndentStyle;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Indent
 *
 * @uses \Ergebnis\Json\Parser\IndentSize
 * @uses \Ergebnis\Json\Parser\IndentStyle
 */
final class IndentTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testNoneReturnsIndent(): void
    {
        $indent = Indent::none();

        self::assertSame('', $indent->toString());
    }

    /**
     * @dataProvider provideStyleAndCharacter
     */
    public function testCreateReturnsIndent(
        IndentStyle $style,
        string $character
    ): void {
        $size = IndentSize::fromInt(self::faker()->numberBetween(1, 8));

        $indent = Indent::create(
            $size,
            $style,
        );

        $expected = \str_repeat(
            $character,
            $size->toInt(),
        );

        self::assertSame($expected, $indent->toString());
    }

    /**
     * @return \Generator<string, array{0: IndentStyle, 1: string}>
     */
    public static function provideStyleAndCharacter(): iterable
    {
        $values = [
            'space' => [
                IndentStyle::space(),
                ' ',
            ],
            'tab' => [
                IndentStyle::tab(),
                "\t",
            ],
        ];

        foreach ($values as $key => [$style, $character]) {
            yield $key => [
                $style,
                $character,
            ];
        }
    }

    public function testEqualsReturnsFalseWhenValueIsDifferent(): void
    {
        $size = self::faker()->numberBetween(1, 8);

        $one = Indent::create(
            IndentSize::fromInt($size),
            IndentStyle::space(),
        );

        $two = Indent::create(
            IndentSize::fromInt($size + 1),
            IndentStyle::space(),
        );

        self::assertFalse($one->equals($two));
    }

    public function testEqualsReturnsTrueWhenValueIsSame(): void
    {
        $size = self::faker()->numberBetween(1, 8);

        $one = Indent::create(
            IndentSize::fromInt($size),
            IndentStyle::space(),
        );

        $two = Indent::create(
            IndentSize::fromInt($size),
            IndentStyle::space(),
        );

        self::assertTrue($one->equals($two));
    }
}
