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

namespace Ergebnis\Json\Parser;

final class InvalidJson extends \RuntimeException implements Exception
{
    private Position $position;

    public static function expectedBooleanAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Expected "true" or "false" at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function expectedColonAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Expected ":" at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function expectedCommaOrClosingBraceAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Expected "," or "}" at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function expectedCommaOrClosingBracketAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Expected "," or "]" at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function expectedNullAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Expected "null" at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function expectedPropertyNameAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Expected property name at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function invalidEscapeSequenceAt(
        Bytes $character,
        Position $position
    ): self {
        $exception = new self(\sprintf(
            'Invalid escape sequence "\\%s" at line %d, column %d.',
            $character->toEscapedString(),
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function invalidHexDigitsAt(
        Bytes $hexDigits,
        Position $position
    ): self {
        $exception = new self(\sprintf(
            'Invalid hex digits "%s" at line %d, column %d.',
            $hexDigits->toEscapedString(),
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function invalidLowSurrogateAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Invalid low surrogate at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function invalidNumberAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Invalid number at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function malformedUtf8At(Position $position): self
    {
        $exception = new self(\sprintf(
            'Malformed UTF-8 at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function maximumDepthExceededAt(
        MaximumDepth $maximumDepth,
        Position $position
    ): self {
        $exception = new self(\sprintf(
            'Maximum depth of %d exceeded at line %d, column %d.',
            $maximumDepth->toInt(),
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function missingDigitAfterDecimalPointAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Expected digit after decimal point at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function missingDigitInExponentAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Expected digit in exponent at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function missingHexDigitsAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Expected 4 hex digits at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function missingLowSurrogateAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Expected low surrogate after high surrogate at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function unescapedControlCharacterAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Unescaped control character at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function unexpectedCharacterAt(
        Bytes $character,
        Position $position
    ): self {
        $exception = new self(\sprintf(
            'Unexpected character "%s" at line %d, column %d.',
            $character->toEscapedString(),
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function unexpectedEndOfInputAt(Position $position): self
    {
        $exception = new self('Unexpected end of input.');

        $exception->position = $position;

        return $exception;
    }

    public static function unexpectedEndOfInputInArrayAt(Position $position): self
    {
        $exception = new self('Unexpected end of input in array.');

        $exception->position = $position;

        return $exception;
    }

    public static function unexpectedEndOfInputInEscapeSequenceAt(Position $position): self
    {
        $exception = new self('Unexpected end of input in escape sequence.');

        $exception->position = $position;

        return $exception;
    }

    public static function unexpectedEndOfInputInObjectAt(Position $position): self
    {
        $exception = new self('Unexpected end of input in object.');

        $exception->position = $position;

        return $exception;
    }

    public static function unexpectedLowSurrogateAt(Position $position): self
    {
        $exception = new self(\sprintf(
            'Unexpected low surrogate at line %d, column %d.',
            $position->line()->toInt(),
            $position->column()->toInt(),
        ));

        $exception->position = $position;

        return $exception;
    }

    public static function unterminatedStringAt(Position $position): self
    {
        $exception = new self('Unterminated string.');

        $exception->position = $position;

        return $exception;
    }

    public function position(): Position
    {
        return $this->position;
    }
}
