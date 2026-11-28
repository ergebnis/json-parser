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
    use Parser\Test\Util\Helper;

    public function testCompactReturnsFormat(): void
    {
        $format = Parser\Format::compact();

        self::assertEquals(Parser\Indent::none(), $format->indent());
        self::assertEquals(Parser\NewLine::none(), $format->newLine());
        self::assertEquals(Parser\FinalNewLine::none(), $format->finalNewLine());
    }

    public function testCreateThrowsInvalidFormatWhenFinalNewLineIsPresentAndNewLineIsEmpty(): void
    {
        $indent = Parser\Indent::none();
        $newLine = Parser\NewLine::none();
        $finalNewLine = Parser\FinalNewLine::present();

        $this->expectException(Parser\InvalidFormat::class);
        $this->expectExceptionMessage('A final new line requires a new line.');

        Parser\Format::create(
            $indent,
            $newLine,
            $finalNewLine,
        );
    }

    public function testCreateThrowsInvalidFormatWhenIndentIsNotEmptyAndNewLineIsEmpty(): void
    {
        $indent = Parser\Indent::create(
            Parser\IndentSize::fromInt(self::faker()->numberBetween(1, 8)),
            Parser\IndentStyle::space(),
        );
        $newLine = Parser\NewLine::none();
        $finalNewLine = Parser\FinalNewLine::none();

        $this->expectException(Parser\InvalidFormat::class);
        $this->expectExceptionMessage('An indent requires a new line.');

        Parser\Format::create(
            $indent,
            $newLine,
            $finalNewLine,
        );
    }

    /**
     * @dataProvider provideIndentNewLineAndFinalNewLineWhereIndentAndFinalNewLineAreEmptyOrNewLineIsNotEmpty
     */
    public function testCreateReturnsFormatWhenIndentAndFinalNewLineAreEmptyOrNewLineIsNotEmpty(
        Parser\Indent $indent,
        Parser\NewLine $newLine,
        Parser\FinalNewLine $finalNewLine
    ): void {
        $format = Parser\Format::create(
            $indent,
            $newLine,
            $finalNewLine,
        );

        self::assertSame($indent, $format->indent());
        self::assertSame($newLine, $format->newLine());
        self::assertSame($finalNewLine, $format->finalNewLine());
    }

    /**
     * @return \Generator<string, array{0: Parser\Indent, 1: Parser\NewLine, 2: Parser\FinalNewLine}>
     */
    public static function provideIndentNewLineAndFinalNewLineWhereIndentAndFinalNewLineAreEmptyOrNewLineIsNotEmpty(): iterable
    {
        $values = [
            'indent-none-new-line-lf-and-final-new-line-present' => [
                Parser\Indent::none(),
                Parser\NewLine::lf(),
                Parser\FinalNewLine::present(),
            ],
            'indent-none-new-line-none-and-final-new-line-none' => [
                Parser\Indent::none(),
                Parser\NewLine::none(),
                Parser\FinalNewLine::none(),
            ],
            'indent-spaces-new-line-lf-and-final-new-line-none' => [
                Parser\Indent::create(
                    Parser\IndentSize::fromInt(4),
                    Parser\IndentStyle::space(),
                ),
                Parser\NewLine::lf(),
                Parser\FinalNewLine::none(),
            ],
            'indent-tab-new-line-cr-lf-and-final-new-line-present' => [
                Parser\Indent::create(
                    Parser\IndentSize::fromInt(1),
                    Parser\IndentStyle::tab(),
                ),
                Parser\NewLine::crLf(),
                Parser\FinalNewLine::present(),
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
        Parser\Format $expected
    ): void {
        $raw = Parser\Raw::fromString($json);

        $format = Parser\Format::fromRaw($raw);

        self::assertEquals($expected, $format);
    }

    /**
     * @return \Generator<string, array{0: string, 1: Parser\Format}>
     */
    public static function provideJsonAndFormat(): iterable
    {
        $values = [
            'blank-and-whitespace-only-lines-before-indent' => [
                "{\n\n   \n  \"name\": \"ergebnis/json-parser\"\n}",
                Parser\Format::create(
                    Parser\Indent::create(
                        Parser\IndentSize::fromInt(2),
                        Parser\IndentStyle::space(),
                    ),
                    Parser\NewLine::lf(),
                    Parser\FinalNewLine::none(),
                ),
            ],
            'compact' => [
                '{"name":"ergebnis/json-parser"}',
                Parser\Format::compact(),
            ],
            'crlf-tab-final-new-line' => [
                "{\r\n\t\"name\": \"ergebnis/json-parser\"\r\n}\r\n",
                Parser\Format::create(
                    Parser\Indent::create(
                        Parser\IndentSize::fromInt(1),
                        Parser\IndentStyle::tab(),
                    ),
                    Parser\NewLine::crLf(),
                    Parser\FinalNewLine::present(),
                ),
            ],
            'final-new-line-only' => [
                "{\"name\":\"ergebnis/json-parser\"}\n",
                Parser\Format::create(
                    Parser\Indent::none(),
                    Parser\NewLine::lf(),
                    Parser\FinalNewLine::present(),
                ),
            ],
            'lf-four-spaces-final-new-line' => [
                "{\n    \"name\": \"ergebnis/json-parser\",\n    \"keywords\": [\n        \"json\"\n    ]\n}\n",
                Parser\Format::create(
                    Parser\Indent::create(
                        Parser\IndentSize::fromInt(4),
                        Parser\IndentStyle::space(),
                    ),
                    Parser\NewLine::lf(),
                    Parser\FinalNewLine::present(),
                ),
            ],
            'lf-two-spaces' => [
                "{\n  \"name\": \"ergebnis/json-parser\"\n}",
                Parser\Format::create(
                    Parser\Indent::create(
                        Parser\IndentSize::fromInt(2),
                        Parser\IndentStyle::space(),
                    ),
                    Parser\NewLine::lf(),
                    Parser\FinalNewLine::none(),
                ),
            ],
            'new-line-without-indent' => [
                "{\n\"name\": \"ergebnis/json-parser\"\n}",
                Parser\Format::create(
                    Parser\Indent::none(),
                    Parser\NewLine::lf(),
                    Parser\FinalNewLine::none(),
                ),
            ],
            'scalar' => [
                '1',
                Parser\Format::compact(),
            ],
            'spaces-without-new-line' => [
                '{"name": "ergebnis/json-parser", "keywords": ["json"]}',
                Parser\Format::compact(),
            ],
            'tab-followed-by-spaces' => [
                "{\n\t  \"name\": \"ergebnis/json-parser\"\n}",
                Parser\Format::create(
                    Parser\Indent::create(
                        Parser\IndentSize::fromInt(1),
                        Parser\IndentStyle::tab(),
                    ),
                    Parser\NewLine::lf(),
                    Parser\FinalNewLine::none(),
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
