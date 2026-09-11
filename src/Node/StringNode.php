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

namespace Ergebnis\Json\Parser\Node;

use Ergebnis\Json\Parser\InvalidString;
use Ergebnis\Json\Parser\Raw;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc8259#section-7
 */
final class StringNode implements Node
{
    private const CHARACTERS_REQUIRING_ESCAPE = "\"\\\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";
    private const ESCAPES = [
        "\x00" => "\x5Cu0000",
        "\x01" => "\x5Cu0001",
        "\x02" => "\x5Cu0002",
        "\x03" => "\x5Cu0003",
        "\x04" => "\x5Cu0004",
        "\x05" => "\x5Cu0005",
        "\x06" => "\x5Cu0006",
        "\x07" => "\x5Cu0007",
        "\x08" => "\x5Cb",
        "\x09" => "\x5Ct",
        "\x0A" => "\x5Cn",
        "\x0B" => "\x5Cu000b",
        "\x0C" => "\x5Cf",
        "\x0D" => "\x5Cr",
        "\x0E" => "\x5Cu000e",
        "\x0F" => "\x5Cu000f",
        "\x10" => "\x5Cu0010",
        "\x11" => "\x5Cu0011",
        "\x12" => "\x5Cu0012",
        "\x13" => "\x5Cu0013",
        "\x14" => "\x5Cu0014",
        "\x15" => "\x5Cu0015",
        "\x16" => "\x5Cu0016",
        "\x17" => "\x5Cu0017",
        "\x18" => "\x5Cu0018",
        "\x19" => "\x5Cu0019",
        "\x1A" => "\x5Cu001a",
        "\x1B" => "\x5Cu001b",
        "\x1C" => "\x5Cu001c",
        "\x1D" => "\x5Cu001d",
        "\x1E" => "\x5Cu001e",
        "\x1F" => "\x5Cu001f",
        '"' => "\x5C\"",
        "\x5C" => "\x5C\x5C",
    ];
    private string $raw;

    private function __construct(string $raw)
    {
        $this->raw = $raw;
    }

    /**
     * @throws InvalidString
     */
    public static function fromRaw(Raw $raw): self
    {
        $value = $raw->toString();

        $pattern = <<<'EOD'
            /
                \\
                (?:
                    ["\\\/bfnrt]
                    |u(?![dD][89a-fA-F])[0-9a-fA-F]{4}
                    |u[dD][89abAB][0-9a-fA-F]{2}\\u[dD][c-fC-F][0-9a-fA-F]{2}
                )
            /ux
            EOD;

        $withoutEscapeSequences = \preg_replace(
            $pattern,
            '',
            $value,
        );

        if (!\is_string($withoutEscapeSequences)) {
            throw InvalidString::notValidJsonString($raw);
        }

        $length = \strlen($withoutEscapeSequences);

        if (
            2 > $length
            || '"' !== $withoutEscapeSequences[0]
            || '"' !== $withoutEscapeSequences[$length - 1]
            || $length - 2 !== \strcspn($withoutEscapeSequences, self::CHARACTERS_REQUIRING_ESCAPE, 1)
        ) {
            throw InvalidString::notValidJsonString($raw);
        }

        return new self($value);
    }

    /**
     * @internal
     */
    public static function fromParsedRaw(string $raw): self
    {
        return new self($raw);
    }

    /**
     * @throws InvalidString
     */
    public static function fromString(string $value): self
    {
        if (1 !== \preg_match('//u', $value)) {
            throw InvalidString::notValidUtf8($value);
        }

        $escaped = \strtr(
            $value,
            self::ESCAPES,
        );

        return new self('"' . $escaped . '"');
    }

    public function raw(): string
    {
        return $this->raw;
    }

    /**
     * @throws InvalidString
     */
    public function toString(): string
    {
        $value = \json_decode($this->raw);

        /**
         * json_decode() returns mixed; valid raw text always decodes to a string.
         */
        if (!\is_string($value)) {
            throw InvalidString::notValidJsonString(Raw::fromString($this->raw));
        }

        return $value;
    }
}
