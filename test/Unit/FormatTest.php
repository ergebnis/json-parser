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

use Ergebnis\Json\Parser\FinalNewLine;
use Ergebnis\Json\Parser\Format;
use Ergebnis\Json\Parser\Indent;
use Ergebnis\Json\Parser\IndentSize;
use Ergebnis\Json\Parser\IndentStyle;
use Ergebnis\Json\Parser\InvalidFormat;
use Ergebnis\Json\Parser\NewLine;
use Ergebnis\Json\Parser\Raw;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Format
 *
 * @uses \Ergebnis\Json\Parser\FinalNewLine
 * @uses \Ergebnis\Json\Parser\Indent
 * @uses \Ergebnis\Json\Parser\IndentSize
 * @uses \Ergebnis\Json\Parser\IndentStyle
 * @uses \Ergebnis\Json\Parser\InvalidFormat
 * @uses \Ergebnis\Json\Parser\NewLine
 * @uses \Ergebnis\Json\Parser\Raw
 */
final class FormatTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testCompactReturnsFormat(): void
    {
        $format = Format::compact();

        self::assertEquals(Indent::none(), $format->indent());
        self::assertEquals(NewLine::none(), $format->newLine());
        self::assertEquals(FinalNewLine::none(), $format->finalNewLine());
    }

    public function testCreateThrowsInvalidFormatWhenFinalNewLineIsPresentAndNewLineIsEmpty(): void
    {
        $indent = Indent::none();
        $newLine = NewLine::none();
        $finalNewLine = FinalNewLine::present();

        $this->expectException(InvalidFormat::class);
        $this->expectExceptionMessage('A final new line requires a new line.');

        Format::create(
            $indent,
            $newLine,
            $finalNewLine,
        );
    }

    public function testCreateThrowsInvalidFormatWhenIndentIsNotEmptyAndNewLineIsEmpty(): void
    {
        $indent = Indent::create(
            IndentSize::fromInt(self::faker()->numberBetween(1, 8)),
            IndentStyle::space(),
        );
        $newLine = NewLine::none();
        $finalNewLine = FinalNewLine::none();

        $this->expectException(InvalidFormat::class);
        $this->expectExceptionMessage('An indent requires a new line.');

        Format::create(
            $indent,
            $newLine,
            $finalNewLine,
        );
    }

    /**
     * @dataProvider provideIndentNewLineAndFinalNewLineWhereIndentAndFinalNewLineAreEmptyOrNewLineIsNotEmpty
     */
    public function testCreateReturnsFormatWhenIndentAndFinalNewLineAreEmptyOrNewLineIsNotEmpty(
        Indent $indent,
        NewLine $newLine,
        FinalNewLine $finalNewLine
    ): void {
        $format = Format::create(
            $indent,
            $newLine,
            $finalNewLine,
        );

        self::assertSame($indent, $format->indent());
        self::assertSame($newLine, $format->newLine());
        self::assertSame($finalNewLine, $format->finalNewLine());
    }

    /**
     * @return \Generator<string, array{0: Indent, 1: NewLine, 2: FinalNewLine}>
     */
    public static function provideIndentNewLineAndFinalNewLineWhereIndentAndFinalNewLineAreEmptyOrNewLineIsNotEmpty(): iterable
    {
        $values = [
            'indent-none-new-line-none-and-final-new-line-none' => [
                Indent::none(),
                NewLine::none(),
                FinalNewLine::none(),
            ],
            'indent-none-new-line-lf-and-final-new-line-present' => [
                Indent::none(),
                NewLine::lf(),
                FinalNewLine::present(),
            ],
            'indent-spaces-new-line-lf-and-final-new-line-none' => [
                Indent::create(
                    IndentSize::fromInt(4),
                    IndentStyle::space(),
                ),
                NewLine::lf(),
                FinalNewLine::none(),
            ],
            'indent-tab-new-line-cr-lf-and-final-new-line-present' => [
                Indent::create(
                    IndentSize::fromInt(1),
                    IndentStyle::tab(),
                ),
                NewLine::crLf(),
                FinalNewLine::present(),
            ],
        ];

        foreach ($values as $key => [$indent, $newLine, $finalNewLine]) {
            yield $key => [
                $indent,
                $newLine,
                $finalNewLine,
            ];
        }
    }

    /**
     * @dataProvider provideJsonAndFormat
     */
    public function testFromRawReturnsFormatDetectedFromJson(
        string $json,
        Format $expected
    ): void {
        $raw = Raw::fromString($json);

        $format = Format::fromRaw($raw);

        self::assertEquals($expected, $format);
    }

    /**
     * @return \Generator<string, array{0: string, 1: Format}>
     */
    public static function provideJsonAndFormat(): iterable
    {
        $values = [
            'scalar' => [
                '1',
                Format::compact(),
            ],
            'compact' => [
                '{"name":"ergebnis/json-parser"}',
                Format::compact(),
            ],
            'spaces-without-new-line' => [
                '{"name": "ergebnis/json-parser", "keywords": ["json"]}',
                Format::compact(),
            ],
            'final-new-line-only' => [
                "{\"name\":\"ergebnis/json-parser\"}\n",
                Format::create(
                    Indent::none(),
                    NewLine::lf(),
                    FinalNewLine::present(),
                ),
            ],
            'new-line-without-indent' => [
                "{\n\"name\": \"ergebnis/json-parser\"\n}",
                Format::create(
                    Indent::none(),
                    NewLine::lf(),
                    FinalNewLine::none(),
                ),
            ],
            'lf-four-spaces-final-new-line' => [
                "{\n    \"name\": \"ergebnis/json-parser\",\n    \"keywords\": [\n        \"json\"\n    ]\n}\n",
                Format::create(
                    Indent::create(
                        IndentSize::fromInt(4),
                        IndentStyle::space(),
                    ),
                    NewLine::lf(),
                    FinalNewLine::present(),
                ),
            ],
            'lf-two-spaces' => [
                "{\n  \"name\": \"ergebnis/json-parser\"\n}",
                Format::create(
                    Indent::create(
                        IndentSize::fromInt(2),
                        IndentStyle::space(),
                    ),
                    NewLine::lf(),
                    FinalNewLine::none(),
                ),
            ],
            'crlf-tab-final-new-line' => [
                "{\r\n\t\"name\": \"ergebnis/json-parser\"\r\n}\r\n",
                Format::create(
                    Indent::create(
                        IndentSize::fromInt(1),
                        IndentStyle::tab(),
                    ),
                    NewLine::crLf(),
                    FinalNewLine::present(),
                ),
            ],
            'blank-and-whitespace-only-lines-before-indent' => [
                "{\n\n   \n  \"name\": \"ergebnis/json-parser\"\n}",
                Format::create(
                    Indent::create(
                        IndentSize::fromInt(2),
                        IndentStyle::space(),
                    ),
                    NewLine::lf(),
                    FinalNewLine::none(),
                ),
            ],
            'tab-followed-by-spaces' => [
                "{\n\t  \"name\": \"ergebnis/json-parser\"\n}",
                Format::create(
                    Indent::create(
                        IndentSize::fromInt(1),
                        IndentStyle::tab(),
                    ),
                    NewLine::lf(),
                    FinalNewLine::none(),
                ),
            ],
        ];

        foreach ($values as $key => [$json, $format]) {
            yield $key => [
                $json,
                $format,
            ];
        }
    }
}
