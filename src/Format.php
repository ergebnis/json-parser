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

final class Format
{
    private Indent $indent;
    private NewLine $newLine;
    private FinalNewLine $finalNewLine;

    private function __construct(
        Indent $indent,
        NewLine $newLine,
        FinalNewLine $finalNewLine
    ) {
        $this->indent = $indent;
        $this->newLine = $newLine;
        $this->finalNewLine = $finalNewLine;
    }

    public static function compact(): self
    {
        return new self(
            Indent::none(),
            NewLine::none(),
            FinalNewLine::none(),
        );
    }

    /**
     * @throws InvalidFormat
     */
    public static function create(
        Indent $indent,
        NewLine $newLine,
        FinalNewLine $finalNewLine
    ): self {
        if (
            $newLine->equals(NewLine::none())
            && !$indent->equals(Indent::none())
        ) {
            throw InvalidFormat::indentWithoutNewLine();
        }

        if (
            $newLine->equals(NewLine::none())
            && $finalNewLine->equals(FinalNewLine::present())
        ) {
            throw InvalidFormat::finalNewLineWithoutNewLine();
        }

        return new self(
            $indent,
            $newLine,
            $finalNewLine,
        );
    }

    public static function fromRaw(Raw $raw): self
    {
        $json = $raw->toString();

        $newLineMatches = [];

        if (1 !== \preg_match('/\r\n|\n/', $json, $newLineMatches)) {
            return self::compact();
        }

        $newLine = NewLine::lf();

        if ("\r\n" === $newLineMatches[0]) {
            $newLine = NewLine::crLf();
        }

        $indent = Indent::none();

        $indentMatches = [];

        if (1 === \preg_match('/(?:\r\n|\n)( ++|\t++)(?=[^\r\n])/', $json, $indentMatches)) {
            $indentStyle = IndentStyle::space();

            if ("\t" === $indentMatches[1][0]) {
                $indentStyle = IndentStyle::tab();
            }

            $indent = Indent::create(
                IndentSize::fromInt(\strlen($indentMatches[1])),
                $indentStyle,
            );
        }

        $finalNewLine = FinalNewLine::none();

        if (1 === \preg_match('/(?:\r\n|\n)\z/', $json)) {
            $finalNewLine = FinalNewLine::present();
        }

        return new self(
            $indent,
            $newLine,
            $finalNewLine,
        );
    }

    public function indent(): Indent
    {
        return $this->indent;
    }

    public function newLine(): NewLine
    {
        return $this->newLine;
    }

    public function finalNewLine(): FinalNewLine
    {
        return $this->finalNewLine;
    }
}
