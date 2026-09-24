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

use Ergebnis\Json\Parser\InvalidJson;
use Ergebnis\Json\Parser\InvalidString;
use Ergebnis\Json\Parser\MaximumDepth;
use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Parser;
use Ergebnis\Json\Parser\Raw;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;
use Symfony\Component\Finder;

/**
 * @covers \Ergebnis\Json\Parser\Node\StringNode
 *
 * @uses \Ergebnis\Json\Parser\Bytes
 * @uses \Ergebnis\Json\Parser\InvalidJson
 * @uses \Ergebnis\Json\Parser\InvalidString
 * @uses \Ergebnis\Json\Parser\MaximumDepth
 * @uses \Ergebnis\Json\Parser\Parser
 * @uses \Ergebnis\Json\Parser\Raw
 */
final class StringNodeTest extends Framework\TestCase
{
    use Test\Util\Helper;

    /**
     * @dataProvider provideInvalidRaw
     */
    public function testFromRawThrowsInvalidStringWhenRawIsInvalid(string $value): void
    {
        $raw = Raw::fromString($value);

        $this->expectException(InvalidString::class);

        Node\StringNode::fromRaw($raw);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideInvalidRaw(): iterable
    {
        $values = [
            'control-character-at-end' => "\"a\x1F\"",
            'control-character-at-start' => "\"\x1Fa\"",
            'control-character-instead-of-closing-quote' => "\"a\x1F",
            'empty' => '',
            'escape-sequence-with-invalid-character' => "\"\x5Cx\"",
            'escape-sequence-with-too-few-hex-digits' => "\"\x5Cu12\"",
            'escaped-closing-quote' => "\"a\x5C\"",
            'high-surrogate-followed-by-non-low-surrogate' => "\"\x5CuD800\x5Cu0041\"",
            'high-surrogate-without-low-surrogate' => "\"\x5CuD800\"",
            'invalid-utf-8' => "\"\xC3\x28\"",
            'leading-whitespace' => ' "a"',
            'low-surrogate-without-high-surrogate' => "\"\x5CuDC00\"",
            'missing-closing-quote' => '"a',
            'missing-opening-quote' => 'a"',
            'nul' => "\"\x00\"",
            'overlong-utf-8' => "\"\xC0\xAF\"",
            'quote' => '"',
            'trailing-new-line' => "\"a\"\n",
            'unescaped-quote' => '"a"b"',
            'unquoted' => 'a',
            'utf-8-beyond-last-code-point' => "\"\xF4\x90\x80\x80\"",
            'utf-8-encoded-surrogate' => "\"\xED\xA0\x80\"",
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideRawFromJsonTestSuiteThatParserDoesNotAccept
     */
    public function testFromRawThrowsInvalidStringWhenParserDoesNotAcceptRaw(string $value): void
    {
        $raw = Raw::fromString($value);

        $this->expectException(InvalidString::class);

        Node\StringNode::fromRaw($raw);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideRawFromJsonTestSuiteThatParserDoesNotAccept(): iterable
    {
        foreach (self::rawStringsFromJsonTestSuite() as $key => $value) {
            if (!self::parserAccepts($value)) {
                yield $key => [
                    $value,
                ];
            }
        }
    }

    /**
     * @dataProvider provideValidRaw
     */
    public function testFromRawReturnsStringNodeWhenRawIsValid(string $value): void
    {
        $raw = Raw::fromString($value);

        $node = Node\StringNode::fromRaw($raw);

        self::assertSame($value, $node->raw());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideValidRaw(): iterable
    {
        $values = [
            'delete-character' => "\"\x7F\"",
            'empty' => '""',
            'escape-sequences' => "\"\x5C\"\x5C\x5C\x5C/\x5Cb\x5Cf\x5Cn\x5Cr\x5Ct\"",
            'escaped-backslash-before-u' => "\"\x5C\x5CuD800\"",
            'four-byte-utf-8' => "\"\xF0\x9F\x98\x80\"",
            'many-escape-sequences' => '"' . \str_repeat("\x5Cn", 2000000) . '"',
            'non-ascii' => "\"caf\xC3\xA9\"",
            'surrogate-pair-lowercase' => "\"\x5Cud834\x5Cudd1e\"",
            'surrogate-pair-uppercase' => "\"\x5CuD834\x5CuDD1E\"",
            'unicode-escape-sequence-lowercase' => "\"\x5Cu00e9\"",
            'unicode-escape-sequence-uppercase' => "\"\x5Cu00E9\"",
            'word' => '"' . self::faker()->word() . '"',
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideRawFromJsonTestSuiteThatParserAccepts
     */
    public function testFromRawReturnsStringNodeWhenParserAcceptsRaw(string $value): void
    {
        $raw = Raw::fromString($value);

        $node = Node\StringNode::fromRaw($raw);

        self::assertSame($value, $node->raw());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideRawFromJsonTestSuiteThatParserAccepts(): iterable
    {
        foreach (self::rawStringsFromJsonTestSuite() as $key => $value) {
            if (self::parserAccepts($value)) {
                yield $key => [
                    $value,
                ];
            }
        }
    }

    public function testFromParsedRawReturnsStringNode(): void
    {
        $raw = '"' . self::faker()->sentence() . '"';

        $node = Node\StringNode::fromParsedRaw($raw);

        self::assertSame($raw, $node->raw());
    }

    /**
     * @dataProvider provideValueThatIsNotValidUtf8
     */
    public function testFromStringThrowsInvalidStringWhenValueIsNotValidUtf8(string $value): void
    {
        $this->expectException(InvalidString::class);

        Node\StringNode::fromString($value);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideValueThatIsNotValidUtf8(): iterable
    {
        $values = [
            'continuation-byte' => "\x80",
            'overlong' => "\xC0\xAF",
            'truncated' => "\xC3",
            'utf-8-encoded-surrogate' => "\xED\xA0\x80",
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideValueThatIsValidUtf8
     */
    public function testFromStringReturnsStringNodeWhenValueIsValidUtf8(string $value): void
    {
        $node = Node\StringNode::fromString($value);

        $expected = \json_encode(
            $value,
            \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_LINE_TERMINATORS | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
        );

        self::assertSame($expected, $node->raw());
        self::assertSame($value, $node->toString());
        self::assertEquals($node, Node\StringNode::fromRaw(Raw::fromString($node->raw())));
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideValueThatIsValidUtf8(): iterable
    {
        foreach (\range(0x00, 0x1F) as $byte) {
            yield \sprintf(
                'control-character-%02x',
                $byte,
            ) => [
                \chr($byte),
            ];
        }

        $values = [
            'backslash' => "\x5C",
            'delete-character' => "\x7F",
            'empty' => '',
            'line-separator' => "\xE2\x80\xA8",
            'non-ascii' => "caf\xC3\xA9",
            'paragraph-separator' => "\xE2\x80\xA9",
            'quote' => '"',
            'sentence' => self::faker()->sentence(),
            'slash' => '/',
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideRawAndValue
     */
    public function testToStringReturnsValueWithEscapeSequencesDecoded(
        string $raw,
        string $value
    ): void {
        $node = Node\StringNode::fromRaw(Raw::fromString($raw));

        self::assertSame($value, $node->toString());
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function provideRawAndValue(): iterable
    {
        $sentence = self::faker()->sentence();

        $values = [
            'empty' => [
                '""',
                '',
            ],
            'escaped-backslash' => [
                "\"\x5C\x5C\"",
                "\x5C",
            ],
            'escaped-backspace' => [
                "\"\x5Cb\"",
                "\x08",
            ],
            'escaped-carriage-return' => [
                "\"\x5Cr\"",
                "\x0D",
            ],
            'escaped-form-feed' => [
                "\"\x5Cf\"",
                "\x0C",
            ],
            'escaped-line-feed' => [
                "\"\x5Cn\"",
                "\x0A",
            ],
            'escaped-quote' => [
                "\"\x5C\"\"",
                '"',
            ],
            'escaped-slash' => [
                "\"\x5C/\"",
                '/',
            ],
            'escaped-tab' => [
                "\"\x5Ct\"",
                "\x09",
            ],
            'non-ascii' => [
                "\"caf\xC3\xA9\"",
                "caf\xC3\xA9",
            ],
            'unescaped' => [
                '"' . $sentence . '"',
                $sentence,
            ],
            'unicode-escape' => [
                "\"caf\x5Cu00e9\"",
                "caf\xC3\xA9",
            ],
            'unicode-escape-of-null' => [
                "\"\x5Cu0000\"",
                "\x00",
            ],
            'unicode-escape-with-capital-letters' => [
                "\"caf\x5Cu00E9\"",
                "caf\xC3\xA9",
            ],
            'unicode-escapes-of-surrogate-pair' => [
                "\"\x5Cud83d\x5Cude00\"",
                "\xF0\x9F\x98\x80",
            ],
        ];

        foreach ($values as $key => [$raw, $value]) {
            yield $key => [
                $raw,
                $value,
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private static function rawStringsFromJsonTestSuite(): array
    {
        $finder = Finder\Finder::create()
            ->files()
            ->in(__DIR__ . '/../../Fixture/JSONTestSuite/test_parsing')
            ->name('*.json')
            ->sortByName();

        $values = [];

        foreach ($finder as $file) {
            $matches = [];

            \preg_match_all(
                '/"(?:[^"\\\\]|\\\\[\s\S])*+"?/',
                $file->getContents(),
                $matches,
            );

            foreach (\array_unique($matches[0]) as $index => $value) {
                $key = \sprintf(
                    '%s #%d',
                    $file->getFilename(),
                    $index,
                );

                $values[$key] = $value;
            }
        }

        return $values;
    }

    private static function parserAccepts(string $value): bool
    {
        $parser = new Parser();

        try {
            $parser->parse(
                Raw::fromString($value),
                MaximumDepth::default(),
            );
        } catch (InvalidJson $exception) {
            return false;
        }

        return true;
    }
}
