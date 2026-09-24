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

/**
 * @see https://datatracker.ietf.org/doc/html/rfc8259#section-9
 */
final class Parser
{
    private string $json = '';
    private MaximumDepth $maximumDepth;
    private int $maximumDepthValue = 0;

    /**
     * @var non-negative-int
     */
    private int $offset = 0;
    private int $length = 0;
    private int $depth = 0;

    /**
     * @throws InvalidJson
     */
    public function parse(
        Raw $raw,
        MaximumDepth $maximumDepth
    ): Node\Node {
        $this->json = $raw->toString();
        $this->maximumDepth = $maximumDepth;
        $this->maximumDepthValue = $maximumDepth->toInt();
        $this->offset = 0;
        $this->length = \strlen($this->json);
        $this->depth = 0;

        $node = $this->parseValue();

        $this->skipWhitespace();

        if ($this->offset < $this->length) {
            throw InvalidJson::unexpectedCharacterAt(
                Bytes::fromString($this->json[$this->offset]),
                $this->positionAt($this->offset),
            );
        }

        return $node;
    }

    /**
     * @throws InvalidJson
     */
    private function parseValue(): Node\Node
    {
        $this->skipWhitespace();

        if ($this->offset >= $this->length) {
            throw InvalidJson::unexpectedEndOfInputAt($this->positionAt($this->offset));
        }

        $char = $this->json[$this->offset];

        if ('{' === $char) {
            $this->increaseDepth();

            $node = $this->parseObject();

            --$this->depth;

            return $node;
        }

        if ('[' === $char) {
            $this->increaseDepth();

            $node = $this->parseArray();

            --$this->depth;

            return $node;
        }

        if ('"' === $char) {
            return $this->parseString();
        }

        if (
            't' === $char
            || 'f' === $char
        ) {
            return $this->parseBoolean();
        }

        if ('n' === $char) {
            return $this->parseNull();
        }

        if (
            '-' === $char
            || (
                '0' <= $char
                && '9' >= $char
            )
        ) {
            return $this->parseNumber();
        }

        throw InvalidJson::unexpectedCharacterAt(
            Bytes::fromString($char),
            $this->positionAt($this->offset),
        );
    }

    /**
     * @throws InvalidJson
     */
    private function increaseDepth(): void
    {
        ++$this->depth;

        if ($this->depth >= $this->maximumDepthValue) {
            throw InvalidJson::maximumDepthExceededAt(
                $this->maximumDepth,
                $this->positionAt($this->offset),
            );
        }
    }

    /**
     * @throws InvalidJson
     */
    private function parseObject(): Node\ObjectNode
    {
        ++$this->offset;

        $this->skipWhitespace();

        if (
            $this->offset < $this->length
            && '}' === $this->json[$this->offset]
        ) {
            ++$this->offset;

            return Node\ObjectNode::create();
        }

        $node = Node\ObjectNode::create();

        while (true) {
            $this->skipWhitespace();

            if (
                $this->offset >= $this->length
                || '"' !== $this->json[$this->offset]
            ) {
                throw InvalidJson::expectedPropertyNameAt($this->positionAt($this->offset));
            }

            $name = $this->parseString();

            $this->skipWhitespace();

            if (
                $this->offset >= $this->length
                || ':' !== $this->json[$this->offset]
            ) {
                throw InvalidJson::expectedColonAt($this->positionAt($this->offset));
            }

            ++$this->offset;

            $property = Node\ObjectProperty::create(
                $name,
                $this->parseValue(),
            );

            $node->addProperty($property);

            $this->skipWhitespace();

            if ($this->offset >= $this->length) {
                throw InvalidJson::unexpectedEndOfInputInObjectAt($this->positionAt($this->offset));
            }

            if ('}' === $this->json[$this->offset]) {
                ++$this->offset;

                return $node;
            }

            if (',' !== $this->json[$this->offset]) {
                throw InvalidJson::expectedCommaOrClosingBraceAt($this->positionAt($this->offset));
            }

            ++$this->offset;
        }
    }

    /**
     * @throws InvalidJson
     */
    private function parseArray(): Node\ArrayNode
    {
        ++$this->offset;

        $this->skipWhitespace();

        if (
            $this->offset < $this->length
            && ']' === $this->json[$this->offset]
        ) {
            ++$this->offset;

            return Node\ArrayNode::create();
        }

        $node = Node\ArrayNode::create();

        while (true) {
            $node->addElement($this->parseValue());

            $this->skipWhitespace();

            if ($this->offset >= $this->length) {
                throw InvalidJson::unexpectedEndOfInputInArrayAt($this->positionAt($this->offset));
            }

            if (']' === $this->json[$this->offset]) {
                ++$this->offset;

                return $node;
            }

            if (',' !== $this->json[$this->offset]) {
                throw InvalidJson::expectedCommaOrClosingBracketAt($this->positionAt($this->offset));
            }

            ++$this->offset;
        }
    }

    private function parseString(): Node\StringNode
    {
        $start = $this->offset;

        $this->consumeString();

        $raw = \substr(
            $this->json,
            $start,
            $this->offset - $start,
        );

        return Node\StringNode::fromParsedRaw($raw);
    }

    /**
     * @throws InvalidJson
     */
    private function consumeString(): void
    {
        ++$this->offset;

        while ($this->offset < $this->length) {
            $char = $this->json[$this->offset];

            if ('"' === $char) {
                ++$this->offset;

                return;
            }

            if ('\\' === $char) {
                $this->consumeEscapeSequence();

                continue;
            }

            $byte = \ord($char);

            if (0x20 > $byte) {
                throw InvalidJson::unescapedControlCharacterAt($this->positionAt($this->offset));
            }

            if (0x80 > $byte) {
                ++$this->offset;

                continue;
            }

            $this->consumeMultibyteCharacter();
        }

        throw InvalidJson::unterminatedStringAt($this->positionAt($this->offset));
    }

    /**
     * @throws InvalidJson
     */
    private function consumeEscapeSequence(): void
    {
        ++$this->offset;

        if ($this->offset >= $this->length) {
            throw InvalidJson::unexpectedEndOfInputInEscapeSequenceAt($this->positionAt($this->offset));
        }

        $charOffset = $this->offset;
        $char = $this->json[$this->offset];
        ++$this->offset;

        if ('u' === $char) {
            $this->consumeUnicodeEscape();

            return;
        }

        $index = \strpos(
            '"\\/bfnrt',
            $char,
        );

        if (false === $index) {
            throw InvalidJson::invalidEscapeSequenceAt(
                Bytes::fromString($char),
                $this->positionAt($charOffset),
            );
        }
    }

    /**
     * @throws InvalidJson
     */
    private function consumeUnicodeEscape(): void
    {
        $codePointOffset = $this->offset;
        $codePoint = $this->parseHexQuad();

        if (
            0xDC00 <= $codePoint
            && 0xDFFF >= $codePoint
        ) {
            throw InvalidJson::unexpectedLowSurrogateAt($this->positionAt($codePointOffset));
        }

        if (
            0xD800 > $codePoint
            || 0xDBFF < $codePoint
        ) {
            return;
        }

        if (
            $this->offset + 1 >= $this->length
            || '\\' !== $this->json[$this->offset]
            || $this->json[$this->offset + 1] !== 'u'
        ) {
            throw InvalidJson::missingLowSurrogateAt($this->positionAt($this->offset));
        }

        $this->offset += 2;

        $lowSurrogateOffset = $this->offset;
        $lowSurrogate = $this->parseHexQuad();

        if (
            0xDC00 > $lowSurrogate
            || 0xDFFF < $lowSurrogate
        ) {
            throw InvalidJson::invalidLowSurrogateAt($this->positionAt($lowSurrogateOffset));
        }
    }

    /**
     * @see https://datatracker.ietf.org/doc/html/rfc3629#section-4
     *
     * @throws InvalidJson
     */
    private function consumeMultibyteCharacter(): void
    {
        $pattern = <<<'EOD'
            /
                \G
                (?:
                    [\xC2-\xDF][\x80-\xBF]
                    |\xE0[\xA0-\xBF][\x80-\xBF]
                    |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}
                    |\xED[\x80-\x9F][\x80-\xBF]
                    |\xF0[\x90-\xBF][\x80-\xBF]{2}
                    |[\xF1-\xF3][\x80-\xBF]{3}
                    |\xF4[\x80-\x8F][\x80-\xBF]{2}
                )
            /x
            EOD;

        $matches = [];

        $matched = \preg_match(
            $pattern,
            $this->json,
            $matches,
            0,
            $this->offset,
        );

        if (1 !== $matched) {
            throw InvalidJson::malformedUtf8At($this->positionAt($this->offset));
        }

        $this->offset += \strlen($matches[0]);
    }

    /**
     * @throws InvalidJson
     */
    private function parseHexQuad(): int
    {
        if ($this->offset + 4 > $this->length) {
            throw InvalidJson::missingHexDigitsAt($this->positionAt($this->offset));
        }

        $hex = \substr(
            $this->json,
            $this->offset,
            4,
        );

        $hexDigitCount = \strspn(
            $hex,
            '0123456789abcdefABCDEF',
        );

        if (4 !== $hexDigitCount) {
            throw InvalidJson::invalidHexDigitsAt(
                Bytes::fromString($hex),
                $this->positionAt($this->offset),
            );
        }

        $this->offset += 4;

        return \intval(
            $hex,
            16,
        );
    }

    /**
     * @throws InvalidJson
     */
    private function parseBoolean(): Node\BooleanNode
    {
        if ($this->consumeLiteral('true')) {
            return Node\BooleanNode::fromBool(true);
        }

        if ($this->consumeLiteral('false')) {
            return Node\BooleanNode::fromBool(false);
        }

        throw InvalidJson::expectedBooleanAt($this->positionAt($this->offset));
    }

    /**
     * @throws InvalidJson
     */
    private function parseNull(): Node\NullNode
    {
        if ($this->consumeLiteral('null')) {
            return Node\NullNode::create();
        }

        throw InvalidJson::expectedNullAt($this->positionAt($this->offset));
    }

    private function consumeLiteral(string $literal): bool
    {
        $literalLength = \strlen($literal);

        $candidate = \substr(
            $this->json,
            $this->offset,
            $literalLength,
        );

        if ($candidate !== $literal) {
            return false;
        }

        $this->offset += $literalLength;

        return true;
    }

    /**
     * @throws InvalidJson
     */
    private function parseNumber(): Node\NumberNode
    {
        $start = $this->offset;

        if ('-' === $this->json[$this->offset]) {
            ++$this->offset;
        }

        if ($this->offset >= $this->length) {
            throw InvalidJson::invalidNumberAt($this->positionAt($this->offset));
        }

        if ('0' === $this->json[$this->offset]) {
            ++$this->offset;
        } elseif (
            '1' <= $this->json[$this->offset]
            && '9' >= $this->json[$this->offset]
        ) {
            ++$this->offset;

            while (
                $this->offset < $this->length
                && '0' <= $this->json[$this->offset]
                && '9' >= $this->json[$this->offset]
            ) {
                ++$this->offset;
            }
        } else {
            throw InvalidJson::invalidNumberAt($this->positionAt($this->offset));
        }

        if (
            $this->offset < $this->length
            && '.' === $this->json[$this->offset]
        ) {
            ++$this->offset;

            if (
                $this->offset >= $this->length
                || '0' > $this->json[$this->offset]
                || '9' < $this->json[$this->offset]
            ) {
                throw InvalidJson::missingDigitAfterDecimalPointAt($this->positionAt($this->offset));
            }

            while (
                $this->offset < $this->length
                && '0' <= $this->json[$this->offset]
                && '9' >= $this->json[$this->offset]
            ) {
                ++$this->offset;
            }
        }

        if (
            $this->offset < $this->length
            && (
                'e' === $this->json[$this->offset]
                || 'E' === $this->json[$this->offset]
            )
        ) {
            ++$this->offset;

            if (
                $this->offset < $this->length
                && (
                    '+' === $this->json[$this->offset]
                    || '-' === $this->json[$this->offset]
                )
            ) {
                ++$this->offset;
            }

            if (
                $this->offset >= $this->length
                || '0' > $this->json[$this->offset]
                || '9' < $this->json[$this->offset]
            ) {
                throw InvalidJson::missingDigitInExponentAt($this->positionAt($this->offset));
            }

            while (
                $this->offset < $this->length
                && '0' <= $this->json[$this->offset]
                && '9' >= $this->json[$this->offset]
            ) {
                ++$this->offset;
            }
        }

        $raw = \substr(
            $this->json,
            $start,
            $this->offset - $start,
        );

        return Node\NumberNode::fromParsedRaw($raw);
    }

    private function skipWhitespace(): void
    {
        while ($this->offset < $this->length) {
            $char = $this->json[$this->offset];

            if (
                ' ' !== $char
                && "\t" !== $char
                && "\n" !== $char
                && "\r" !== $char
            ) {
                return;
            }

            ++$this->offset;
        }
    }

    /**
     * @param non-negative-int $offset
     */
    private function positionAt(int $offset): Position
    {
        $text = \str_replace(
            [
                "\r\n",
                "\r",
            ],
            "\n",
            \substr(
                $this->json,
                0,
                $offset,
            ),
        );

        $lastLine = $text;

        $lastLineBreak = \strrpos(
            $text,
            "\n",
        );

        if (false !== $lastLineBreak) {
            $lastLine = \substr(
                $text,
                $lastLineBreak + 1,
            );
        }

        $lineBreakCount = \substr_count(
            $text,
            "\n",
        );

        $column = 1;
        $lastLineLength = \strlen($lastLine);

        for ($index = 0; $index < $lastLineLength; ++$index) {
            /**
             * The bytes are valid UTF-8, so a continuation byte (10xxxxxx) continues the character before it.
             */
            if (0x80 !== (\ord($lastLine[$index]) & 0xC0)) {
                ++$column;
            }
        }

        return Position::create(
            Offset::fromInt($offset),
            Line::fromInt($lineBreakCount + 1),
            Column::fromInt($column),
        );
    }
}
