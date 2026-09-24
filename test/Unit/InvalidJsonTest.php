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

use Ergebnis\Json\Parser\Bytes;
use Ergebnis\Json\Parser\Column;
use Ergebnis\Json\Parser\InvalidJson;
use Ergebnis\Json\Parser\Line;
use Ergebnis\Json\Parser\MaximumDepth;
use Ergebnis\Json\Parser\Offset;
use Ergebnis\Json\Parser\Position;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\InvalidJson
 *
 * @uses \Ergebnis\Json\Parser\Bytes
 * @uses \Ergebnis\Json\Parser\Column
 * @uses \Ergebnis\Json\Parser\Line
 * @uses \Ergebnis\Json\Parser\MaximumDepth
 * @uses \Ergebnis\Json\Parser\Offset
 * @uses \Ergebnis\Json\Parser\Position
 */
final class InvalidJsonTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testExpectedBooleanAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::expectedBooleanAt($position);

        $message = \sprintf(
            'Expected "true" or "false" at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testExpectedColonAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::expectedColonAt($position);

        $message = \sprintf(
            'Expected ":" at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testExpectedCommaOrClosingBraceAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::expectedCommaOrClosingBraceAt($position);

        $message = \sprintf(
            'Expected "," or "}" at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testExpectedCommaOrClosingBracketAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::expectedCommaOrClosingBracketAt($position);

        $message = \sprintf(
            'Expected "," or "]" at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testExpectedNullAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::expectedNullAt($position);

        $message = \sprintf(
            'Expected "null" at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testExpectedPropertyNameAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::expectedPropertyNameAt($position);

        $message = \sprintf(
            'Expected property name at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    /**
     * @dataProvider providePrintableCharacter
     */
    public function testInvalidEscapeSequenceAtReturnsInvalidJsonWhenCharacterIsPrintable(string $character): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::invalidEscapeSequenceAt(
            Bytes::fromString($character),
            $position,
        );

        $message = \sprintf(
            'Invalid escape sequence "\\%s" at line %d, column %d.',
            $character,
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    /**
     * @dataProvider provideNotPrintableCharacterAndEscapedCharacter
     */
    public function testInvalidEscapeSequenceAtReturnsInvalidJsonWhenCharacterIsNotPrintable(
        string $character,
        string $escapedCharacter
    ): void {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::invalidEscapeSequenceAt(
            Bytes::fromString($character),
            $position,
        );

        $message = \sprintf(
            'Invalid escape sequence "\\%s" at line %d, column %d.',
            $escapedCharacter,
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function provideNotPrintableCharacterAndEscapedCharacter(): iterable
    {
        $values = [
            'null' => [
                "\x00",
                '\x00',
            ],
            'unit-separator' => [
                "\x1F",
                '\x1F',
            ],
            'delete' => [
                "\x7F",
                '\x7F',
            ],
            'first-byte-of-multibyte-character' => [
                "\xC3",
                '\xC3',
            ],
            'last-byte' => [
                "\xFF",
                '\xFF',
            ],
        ];

        foreach ($values as $key => [$character, $escapedCharacter]) {
            yield $key => [
                $character,
                $escapedCharacter,
            ];
        }
    }

    public function testInvalidHexDigitsAtReturnsInvalidJsonWhenHexDigitsArePrintable(): void
    {
        $faker = self::faker();

        $hexDigits = $faker->lexify('????');
        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::invalidHexDigitsAt(
            Bytes::fromString($hexDigits),
            $position,
        );

        $message = \sprintf(
            'Invalid hex digits "%s" at line %d, column %d.',
            $hexDigits,
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    /**
     * @dataProvider provideNotPrintableHexDigitsAndEscapedHexDigits
     */
    public function testInvalidHexDigitsAtReturnsInvalidJsonWhenHexDigitsAreNotPrintable(
        string $hexDigits,
        string $escapedHexDigits
    ): void {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::invalidHexDigitsAt(
            Bytes::fromString($hexDigits),
            $position,
        );

        $message = \sprintf(
            'Invalid hex digits "%s" at line %d, column %d.',
            $escapedHexDigits,
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function provideNotPrintableHexDigitsAndEscapedHexDigits(): iterable
    {
        $values = [
            'control-characters' => [
                "\x00\x01\x1F\x7F",
                '\x00\x01\x1F\x7F',
            ],
            'multibyte-character' => [
                "00\xC3\xA9",
                '00\xC3\xA9',
            ],
        ];

        foreach ($values as $key => [$hexDigits, $escapedHexDigits]) {
            yield $key => [
                $hexDigits,
                $escapedHexDigits,
            ];
        }
    }

    public function testInvalidLowSurrogateAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::invalidLowSurrogateAt($position);

        $message = \sprintf(
            'Invalid low surrogate at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testInvalidNumberAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::invalidNumberAt($position);

        $message = \sprintf(
            'Invalid number at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testMalformedUtf8AtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::malformedUtf8At($position);

        $message = \sprintf(
            'Malformed UTF-8 at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testMaximumDepthExceededAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $maximumDepth = MaximumDepth::fromInt($faker->numberBetween(1, 512));
        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::maximumDepthExceededAt(
            $maximumDepth,
            $position,
        );

        $message = \sprintf(
            'Maximum depth of %d exceeded at line %d, column %d.',
            $maximumDepth->toInt(),
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testMissingDigitAfterDecimalPointAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::missingDigitAfterDecimalPointAt($position);

        $message = \sprintf(
            'Expected digit after decimal point at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testMissingDigitInExponentAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::missingDigitInExponentAt($position);

        $message = \sprintf(
            'Expected digit in exponent at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testMissingHexDigitsAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::missingHexDigitsAt($position);

        $message = \sprintf(
            'Expected 4 hex digits at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testMissingLowSurrogateAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::missingLowSurrogateAt($position);

        $message = \sprintf(
            'Expected low surrogate after high surrogate at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testUnescapedControlCharacterAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::unescapedControlCharacterAt($position);

        $message = \sprintf(
            'Unescaped control character at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    /**
     * @dataProvider providePrintableCharacter
     */
    public function testUnexpectedCharacterAtReturnsInvalidJsonWhenCharacterIsPrintable(string $character): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::unexpectedCharacterAt(
            Bytes::fromString($character),
            $position,
        );

        $message = \sprintf(
            'Unexpected character "%s" at line %d, column %d.',
            $character,
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function providePrintableCharacter(): iterable
    {
        $values = [
            'space' => ' ',
            'letter' => self::faker()->randomLetter(),
            'tilde' => '~',
        ];

        foreach ($values as $key => $character) {
            yield $key => [
                $character,
            ];
        }
    }

    /**
     * @dataProvider provideNotPrintableCharacterAndEscapedCharacter
     */
    public function testUnexpectedCharacterAtReturnsInvalidJsonWhenCharacterIsNotPrintable(
        string $character,
        string $escapedCharacter
    ): void {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::unexpectedCharacterAt(
            Bytes::fromString($character),
            $position,
        );

        $message = \sprintf(
            'Unexpected character "%s" at line %d, column %d.',
            $escapedCharacter,
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testUnexpectedEndOfInputAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::unexpectedEndOfInputAt($position);

        self::assertSame('Unexpected end of input.', $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testUnexpectedEndOfInputInArrayAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::unexpectedEndOfInputInArrayAt($position);

        self::assertSame('Unexpected end of input in array.', $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testUnexpectedEndOfInputInEscapeSequenceAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::unexpectedEndOfInputInEscapeSequenceAt($position);

        self::assertSame('Unexpected end of input in escape sequence.', $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testUnexpectedEndOfInputInObjectAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::unexpectedEndOfInputInObjectAt($position);

        self::assertSame('Unexpected end of input in object.', $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testUnexpectedLowSurrogateAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::unexpectedLowSurrogateAt($position);

        $message = \sprintf(
            'Unexpected low surrogate at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame($position, $exception->position());
    }

    public function testUnterminatedStringAtReturnsInvalidJson(): void
    {
        $faker = self::faker();

        $position = Position::create(
            Offset::fromInt($faker->numberBetween(0, 100)),
            Line::fromInt($faker->numberBetween(1, 100)),
            Column::fromInt($faker->numberBetween(1, 100)),
        );

        $exception = InvalidJson::unterminatedStringAt($position);

        self::assertSame('Unterminated string.', $exception->getMessage());
        self::assertSame($position, $exception->position());
    }
}
