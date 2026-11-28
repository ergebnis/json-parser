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

use Ergebnis\Json\Parser;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Indent
 *
 * @uses \Ergebnis\Json\Parser\IndentSize
 * @uses \Ergebnis\Json\Parser\IndentStyle
 */
final class IndentTest extends Framework\TestCase
{
    use Parser\Test\Util\Helper;

    public function testNoneReturnsIndent(): void
    {
        $indent = Parser\Indent::none();

        self::assertSame('', $indent->toString());
    }

    /**
     * @dataProvider provideStyleAndCharacter
     */
    public function testCreateReturnsIndent(
        Parser\IndentStyle $style,
        string $character
    ): void {
        $size = Parser\IndentSize::fromInt(self::faker()->numberBetween(1, 8));

        $indent = Parser\Indent::create(
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
     * @return \Generator<string, array{0: Parser\IndentStyle, 1: string}>
     */
    public static function provideStyleAndCharacter(): iterable
    {
        $values = [
            'space' => [
                Parser\IndentStyle::space(),
                ' ',
            ],
            'tab' => [
                Parser\IndentStyle::tab(),
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

        $one = Parser\Indent::create(
            Parser\IndentSize::fromInt($size),
            Parser\IndentStyle::space(),
        );

        $two = Parser\Indent::create(
            Parser\IndentSize::fromInt($size + 1),
            Parser\IndentStyle::space(),
        );

        self::assertFalse($one->equals($two));
    }

    public function testEqualsReturnsTrueWhenValueIsSame(): void
    {
        $size = self::faker()->numberBetween(1, 8);

        $one = Parser\Indent::create(
            Parser\IndentSize::fromInt($size),
            Parser\IndentStyle::space(),
        );

        $two = Parser\Indent::create(
            Parser\IndentSize::fromInt($size),
            Parser\IndentStyle::space(),
        );

        self::assertTrue($one->equals($two));
    }
}
