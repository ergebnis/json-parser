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

use Ergebnis\DataProvider;
use Ergebnis\Json\Parser;
use Ergebnis\Json\Parser\Format;
use Ergebnis\Json\Parser\InvalidJson;
use Ergebnis\Json\Parser\MaximumDepth;
use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Printer;
use Ergebnis\Json\Parser\Raw;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;
use Symfony\Component\Finder;

/**
 * @covers \Ergebnis\Json\Parser\Parser
 *
 * @uses \Ergebnis\Json\Parser\Bytes
 * @uses \Ergebnis\Json\Parser\Column
 * @uses \Ergebnis\Json\Parser\FinalNewLine
 * @uses \Ergebnis\Json\Parser\Format
 * @uses \Ergebnis\Json\Parser\Indent
 * @uses \Ergebnis\Json\Parser\InvalidJson
 * @uses \Ergebnis\Json\Parser\Line
 * @uses \Ergebnis\Json\Parser\MaximumDepth
 * @uses \Ergebnis\Json\Parser\NewLine
 * @uses \Ergebnis\Json\Parser\Node\ArrayNode
 * @uses \Ergebnis\Json\Parser\Node\BooleanNode
 * @uses \Ergebnis\Json\Parser\Node\NullNode
 * @uses \Ergebnis\Json\Parser\Node\NumberNode
 * @uses \Ergebnis\Json\Parser\Node\ObjectNode
 * @uses \Ergebnis\Json\Parser\Node\ObjectProperty
 * @uses \Ergebnis\Json\Parser\Node\StringNode
 * @uses \Ergebnis\Json\Parser\Offset
 * @uses \Ergebnis\Json\Parser\Position
 * @uses \Ergebnis\Json\Parser\Printer
 * @uses \Ergebnis\Json\Parser\Raw
 */
final class ParserTest extends Framework\TestCase
{
    use Test\Util\Helper;

    /**
     * @see https://github.com/nst/JSONTestSuite/tree/1ef36fa01286573e846ac449e8683f8833c5b26a/test_parsing
     */
    private const JSON_TEST_SUITE_PARSING_FILES_WHERE_IMPLEMENTATION_ACCEPTS = [
        'i_number_*.json',
        'i_structure_500_nested_arrays.json',
    ];

    public function testParseThrowsInvalidJsonForArraysExceedingMaximumDepth(): void
    {
        $maximumDepth = MaximumDepth::fromInt(self::faker()->numberBetween(1, 20));
        $raw = Raw::fromString(\str_repeat(
            '[',
            $maximumDepth->toInt(),
        ) . \str_repeat(
            ']',
            $maximumDepth->toInt(),
        ));

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessage(\sprintf(
            'Maximum depth of %d exceeded at line 1, column %d.',
            $maximumDepth->toInt(),
            $maximumDepth->toInt(),
        ));

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForObjectsExceedingMaximumDepth(): void
    {
        $maximumDepth = MaximumDepth::fromInt(self::faker()->numberBetween(1, 20));
        $raw = Raw::fromString(\str_repeat(
            '{"a":',
            $maximumDepth->toInt(),
        ) . '1' . \str_repeat(
            '}',
            $maximumDepth->toInt(),
        ));

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessage(\sprintf(
            'Maximum depth of %d exceeded at line 1, column %d.',
            $maximumDepth->toInt(),
            5 * ($maximumDepth->toInt() - 1) + 1,
        ));

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForArraysExceedingDefaultMaximumDepth(): void
    {
        $raw = Raw::fromString(\str_repeat(
            '[',
            512,
        ) . \str_repeat(
            ']',
            512,
        ));

        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessage('Maximum depth of 512 exceeded at line 1, column 512.');

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseParsesScalarWhenMaximumDepthIsOne(): void
    {
        $raw = Raw::fromString('1');
        $maximumDepth = MaximumDepth::fromInt(1);

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesArraysAtMaximumDepth(): void
    {
        $maximumDepth = MaximumDepth::fromInt(self::faker()->numberBetween(1, 20));
        $raw = Raw::fromString(\str_repeat(
            '[',
            $maximumDepth->toInt() - 1,
        ) . '1' . \str_repeat(
            ']',
            $maximumDepth->toInt() - 1,
        ));

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1'));

        for ($depth = 1; $maximumDepth->toInt() > $depth; ++$depth) {
            $expected = Node\ArrayNode::create($expected);
        }

        self::assertEquals($expected, $node);
    }

    public function testParseParsesObjectsAtMaximumDepth(): void
    {
        $maximumDepth = MaximumDepth::fromInt(self::faker()->numberBetween(1, 20));
        $raw = Raw::fromString(\str_repeat(
            '{"a":',
            $maximumDepth->toInt() - 1,
        ) . '1' . \str_repeat(
            '}',
            $maximumDepth->toInt() - 1,
        ));

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1'));

        for ($depth = 1; $maximumDepth->toInt() > $depth; ++$depth) {
            $expected = Node\ObjectNode::create(Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"a"')),
                $expected,
            ));
        }

        self::assertEquals($expected, $node);
    }

    public function testParseParsesArraysAtDefaultMaximumDepth(): void
    {
        $raw = Raw::fromString(\str_repeat(
            '[',
            511,
        ) . '1' . \str_repeat(
            ']',
            511,
        ));

        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1'));

        for ($depth = 1; 512 > $depth; ++$depth) {
            $expected = Node\ArrayNode::create($expected);
        }

        self::assertEquals($expected, $node);
    }

    public function testParseParsesArraysAtMaximumDepthWhenMaximumDepthIs4096(): void
    {
        $raw = Raw::fromString(\str_repeat(
            '[',
            4095,
        ) . '1' . \str_repeat(
            ']',
            4095,
        ));
        $maximumDepth = MaximumDepth::fromInt(4096);
        $format = Format::compact();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $printer = new Printer();

        $output = $printer->print(
            $node,
            $format,
        );

        self::assertSame($raw->toString(), $output);
    }

    public function testParseParsesSiblingArraysAndObjectsAtMaximumDepth(): void
    {
        $raw = Raw::fromString('[[],{},[]]');
        $maximumDepth = MaximumDepth::fromInt(3);

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ArrayNode::create(
            Node\ArrayNode::create(),
            Node\ObjectNode::create(),
            Node\ArrayNode::create(),
        );

        self::assertEquals($expected, $node);
    }

    public function testParseResetsDepthBeforeParsingDocument(): void
    {
        $maximumDepth = MaximumDepth::fromInt(self::faker()->numberBetween(2, 20));

        $incompleteJson = \str_repeat(
            '[',
            $maximumDepth->toInt() - 1,
        );

        $raw = Raw::fromString(\str_repeat(
            '[',
            $maximumDepth->toInt() - 1,
        ) . '1' . \str_repeat(
            ']',
            $maximumDepth->toInt() - 1,
        ));

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                Raw::fromString($incompleteJson),
                $maximumDepth,
            );

            self::fail('Expected InvalidJson was not thrown.');
        } catch (InvalidJson $exception) {
            self::assertSame('Unexpected end of input.', $exception->getMessage());
        }

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1'));

        for ($depth = 1; $maximumDepth->toInt() > $depth; ++$depth) {
            $expected = Node\ArrayNode::create($expected);
        }

        self::assertEquals($expected, $node);
    }

    /**
     * @see https://github.com/nst/JSONTestSuite/tree/1ef36fa01286573e846ac449e8683f8833c5b26a/test_parsing
     *
     * @dataProvider provideJsonTestSuiteFileThatMustBeRejected
     * @dataProvider provideJsonTestSuiteFileWhereImplementationRejects
     */
    public function testParseThrowsInvalidJsonForJsonTestSuiteFile(string $json): void
    {
        $raw = Raw::fromString($json);
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideJsonTestSuiteFileThatMustBeRejected(): iterable
    {
        $finder = Finder\Finder::create()
            ->files()
            ->in(__DIR__ . '/../Fixture/JSONTestSuite/test_parsing')
            ->name('n_*.json')
            ->sortByName();

        foreach ($finder as $file) {
            yield $file->getRelativePathname() => [
                $file->getContents(),
            ];
        }
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideJsonTestSuiteFileWhereImplementationRejects(): iterable
    {
        $finder = Finder\Finder::create()
            ->files()
            ->in(__DIR__ . '/../Fixture/JSONTestSuite/test_parsing')
            ->name('i_*.json')
            ->notName(self::JSON_TEST_SUITE_PARSING_FILES_WHERE_IMPLEMENTATION_ACCEPTS)
            ->sortByName();

        foreach ($finder as $file) {
            yield $file->getRelativePathname() => [
                $file->getContents(),
            ];
        }
    }

    /**
     * @see https://github.com/nst/JSONTestSuite/tree/1ef36fa01286573e846ac449e8683f8833c5b26a/test_parsing
     *
     * @dataProvider provideJsonTestSuiteFileThatMustBeAccepted
     * @dataProvider provideJsonTestSuiteFileWhereImplementationAccepts
     */
    public function testParseParsesJsonTestSuiteFile(string $json): void
    {
        $raw = Raw::fromString($json);
        $maximumDepth = MaximumDepth::default();
        $format = Format::compact();

        $parser = new Parser\Parser();
        $printer = new Printer();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = $parser->parse(
            Raw::fromString($printed),
            $maximumDepth,
        );

        self::assertEquals($expected, $node);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideJsonTestSuiteFileThatMustBeAccepted(): iterable
    {
        $finder = Finder\Finder::create()
            ->files()
            ->in(__DIR__ . '/../Fixture/JSONTestSuite/test_parsing')
            ->name('y_*.json')
            ->sortByName();

        foreach ($finder as $file) {
            yield $file->getRelativePathname() => [
                $file->getContents(),
            ];
        }
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideJsonTestSuiteFileWhereImplementationAccepts(): iterable
    {
        $finder = Finder\Finder::create()
            ->files()
            ->in(__DIR__ . '/../Fixture/JSONTestSuite/test_parsing')
            ->name(self::JSON_TEST_SUITE_PARSING_FILES_WHERE_IMPLEMENTATION_ACCEPTS)
            ->sortByName();

        foreach ($finder as $file) {
            yield $file->getRelativePathname() => [
                $file->getContents(),
            ];
        }
    }

    /**
     * @see https://github.com/nst/JSONTestSuite/tree/1ef36fa01286573e846ac449e8683f8833c5b26a/test_transform
     *
     * @dataProvider provideJsonTestSuiteTransformFileAndPrinted
     */
    public function testParseAndPrintPreservesJsonTestSuiteTransformFile(
        string $json,
        string $printed
    ): void {
        $raw = Raw::fromString($json);
        $maximumDepth = MaximumDepth::default();
        $format = Format::compact();

        $parser = new Parser\Parser();
        $printer = new Printer();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $output = $printer->print(
            $node,
            $format,
        );

        self::assertSame($printed, $output);
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function provideJsonTestSuiteTransformFileAndPrinted(): iterable
    {
        $values = [
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/number_-9223372036854775808.json' => '[-9223372036854775808]',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/number_-9223372036854775809.json' => '[-9223372036854775809]',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/number_1.0.json' => '[1.0]',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/number_1.000000000000000005.json' => '[1.000000000000000005]',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/number_1000000000000000.json' => '[1000000000000000]',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/number_10000000000000000999.json' => '[10000000000000000999]',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/number_1e-999.json' => '[1E-999]',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/number_1e6.json' => '[1E6]',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/number_9223372036854775807.json' => '[9223372036854775807]',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/number_9223372036854775808.json' => '[9223372036854775808]',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/object_key_nfc_nfd.json' => '{"' . "\xC3\xA9" . '":"NFC","e' . "\xCC\x81" . '":"NFD"}',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/object_key_nfd_nfc.json' => '{"e' . "\xCC\x81" . '":"NFD","' . "\xC3\xA9" . '":"NFC"}',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/object_same_key_different_values.json' => '{"a":1,"a":2}',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/object_same_key_same_value.json' => '{"a":1,"a":1}',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/object_same_key_unclear_values.json' => '{"a":0,"a":-0}',
            __DIR__ . '/../Fixture/JSONTestSuite/test_transform/string_with_escaped_NULL.json' => '["A\\u0000B"]',
        ];

        foreach ($values as $path => $printed) {
            if (!\file_exists($path)) {
                throw new \RuntimeException(\sprintf(
                    'File "%s" does not exist.',
                    $path,
                ));
            }

            $json = \file_get_contents($path);

            if (!\is_string($json)) {
                throw new \RuntimeException(\sprintf(
                    'File "%s" could not be read.',
                    $path,
                ));
            }

            yield $path => [
                $json,
                $printed,
            ];
        }
    }

    public function testParseThrowsInvalidJsonForEmptyInput(): void
    {
        $raw = Raw::fromString('');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForTrailingContent(): void
    {
        $raw = Raw::fromString('null null');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseParsesNull(): void
    {
        $raw = Raw::fromString('null');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NullNode::create();

        self::assertEquals($expected, $node);
    }

    public function testParseParsesNullWithWhitespace(): void
    {
        $raw = Raw::fromString("  null\n");
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NullNode::create();

        self::assertEquals($expected, $node);
    }

    public function testParseParsesTrue(): void
    {
        $raw = Raw::fromString('true');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\BooleanNode::fromBool(true);

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFalse(): void
    {
        $raw = Raw::fromString('false');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\BooleanNode::fromBool(false);

        self::assertEquals($expected, $node);
    }

    /**
     * @dataProvider provideMalformedUtf8AndColumn
     */
    public function testParseThrowsInvalidJsonForMalformedUtf8(
        string $json,
        int $column
    ): void {
        $raw = Raw::fromString($json);
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessage(\sprintf(
            'Malformed UTF-8 at line 1, column %d.',
            $column,
        ));

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: int}>
     */
    public static function provideMalformedUtf8AndColumn(): iterable
    {
        $values = [
            'continuation-byte-without-lead-byte' => [
                "\"\x80\"",
                2,
            ],
            'invalid-continuation-byte' => [
                "\"\xC3\x28\"",
                2,
            ],
            'lead-byte-c0' => [
                "\"\xC0\xAF\"",
                2,
            ],
            'lead-byte-c1' => [
                "\"\xC1\xBF\"",
                2,
            ],
            'lead-byte-f5' => [
                "\"\xF5\x80\x80\x80\"",
                2,
            ],
            'lead-byte-ff' => [
                "\"\xFF\"",
                2,
            ],
            'overlong-three-byte-sequence' => [
                "\"\xE0\x80\x80\"",
                2,
            ],
            'overlong-four-byte-sequence' => [
                "\"\xF0\x80\x80\x80\"",
                2,
            ],
            'utf8-encoded-high-surrogate' => [
                "\"\xED\xA0\x80\"",
                2,
            ],
            'utf8-encoded-low-surrogate' => [
                "\"\xED\xBF\xBF\"",
                2,
            ],
            'above-maximum-code-point' => [
                "\"\xF4\x90\x80\x80\"",
                2,
            ],
            'truncated-two-byte-sequence' => [
                "\"\xC3\"",
                2,
            ],
            'truncated-three-byte-sequence' => [
                "\"\xE2\x82\"",
                2,
            ],
            'truncated-four-byte-sequence' => [
                "\"\xF0\x9F\x98\"",
                2,
            ],
            'truncated-at-end-of-input' => [
                "\"\xC3",
                2,
            ],
            'after-valid-characters' => [
                "\"a\xC3\xA9\x80\"",
                4,
            ],
            'in-property-name' => [
                "{\"\x80\":1}",
                3,
            ],
        ];

        foreach ($values as $key => [$json, $column]) {
            yield $key => [
                $json,
                $column,
            ];
        }
    }

    /**
     * @dataProvider provideString
     */
    public function testParseParsesString(string $json): void
    {
        $raw = Raw::fromString($json);
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\StringNode::fromRaw($raw);

        self::assertEquals($expected, $node);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideString(): iterable
    {
        $values = [
            'empty' => '""',
            'plain' => '"hello"',
            'slash' => '"a/b"',
            'escaped-quote' => '"hello \\"world\\""',
            'escaped-backslash' => '"a\\\\b"',
            'escaped-slash' => '"a\\/b"',
            'escaped-backspace' => '"a\\bb"',
            'escaped-form-feed' => '"a\\fb"',
            'escaped-newline' => '"a\\nb"',
            'escaped-carriage-return' => '"a\\rb"',
            'escaped-tab' => '"a\\tb"',
            'unicode-escape' => '"\\u0041"',
            'unicode-escape-followed-by-hex-digit' => '"\\u00411"',
            'unicode-escape-null-character' => '"\\u0000"',
            'unicode-escape-lowercase-hex' => '"\\u00ff"',
            'unicode-escape-uppercase-hex' => '"\\u00FF"',
            'unicode-escape-mixed-case-hex' => '"\\u00Ff"',
            'unicode-escape-below-surrogates' => '"\\uD7FF"',
            'unicode-escape-above-surrogates' => '"\\uE000"',
            'unicode-escape-maximum' => '"\\uFFFF"',
            'surrogate-pair-minimum' => '"\\uD800\\uDC00"',
            'surrogate-pair-with-highest-low-surrogate' => '"\\uD800\\uDFFF"',
            'surrogate-pair-with-highest-high-surrogate' => '"\\uDBFF\\uDC00"',
            'surrogate-pair-maximum' => '"\\uDBFF\\uDFFF"',
            'literal-delete-character' => "\"\x7F\"",
            'literal-two-byte-minimum' => "\"\xC2\x80\"",
            'literal-two-byte-maximum' => "\"\xDF\xBF\"",
            'literal-three-byte-minimum' => "\"\xE0\xA0\x80\"",
            'literal-three-byte-with-lead-byte-e1' => "\"\xE1\x80\x80\"",
            'literal-three-byte-below-surrogates' => "\"\xED\x9F\xBF\"",
            'literal-three-byte-above-surrogates' => "\"\xEE\x80\x80\"",
            'literal-three-byte-maximum' => "\"\xEF\xBF\xBF\"",
            'literal-four-byte-minimum' => "\"\xF0\x90\x80\x80\"",
            'literal-four-byte-with-lead-byte-f1' => "\"\xF1\x80\x80\x80\"",
            'literal-four-byte-maximum' => "\"\xF4\x8F\xBF\xBF\"",
            'literal-multibyte-character-followed-by-ascii' => "\"caf\xC3\xA9s\"",
            'literal-consecutive-multibyte-characters' => "\"\xE6\x97\xA5\xE6\x9C\xAC\"",
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function testParseThrowsInvalidJsonForHighSurrogateWithoutLowSurrogate(): void
    {
        $raw = Raw::fromString('"\\uD83D"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForUnexpectedLowSurrogate(): void
    {
        $raw = Raw::fromString('"\\uDE00"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForInvalidLowSurrogate(): void
    {
        $raw = Raw::fromString('"\\uD83D\\u0041"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForInvalidEscapeSequence(): void
    {
        $raw = Raw::fromString('"\\x"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForUnterminatedString(): void
    {
        $raw = Raw::fromString('"hello');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForUnescapedControlCharacter(): void
    {
        $raw = Raw::fromString("\"hello\x00world\"");
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForInvalidHexDigits(): void
    {
        $raw = Raw::fromString('"\\uGGGG"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForTruncatedHexQuad(): void
    {
        $raw = Raw::fromString('"\\u00"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseParsesZero(): void
    {
        $raw = Raw::fromString('0');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('0'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesPositiveInteger(): void
    {
        $raw = Raw::fromString('42');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('42'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesNegativeInteger(): void
    {
        $raw = Raw::fromString('-42');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('-42'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesNegativeZero(): void
    {
        $raw = Raw::fromString('-0');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('-0'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithDecimal(): void
    {
        $raw = Raw::fromString('1.5');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1.5'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithTrailingZeros(): void
    {
        $raw = Raw::fromString('1.0');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1.0'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithExponent(): void
    {
        $raw = Raw::fromString('1e10');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1e10'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithNegativeExponent(): void
    {
        $raw = Raw::fromString('1E-5');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1E-5'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithPositiveExponent(): void
    {
        $raw = Raw::fromString('1e+2');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1e+2'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithDecimalAndExponent(): void
    {
        $raw = Raw::fromString('1.5e2');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1.5e2'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesLargeInteger(): void
    {
        $raw = Raw::fromString('99999999999999999999');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw($raw);

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsInvalidJsonForLeadingZeroInNumber(): void
    {
        $raw = Raw::fromString('01');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForMissingDigitAfterDecimalPoint(): void
    {
        $raw = Raw::fromString('1.');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForMissingDigitInExponent(): void
    {
        $raw = Raw::fromString('1e');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForMinusWithoutDigit(): void
    {
        $raw = Raw::fromString('-');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    /**
     * @dataProvider provideInvalidNumber
     */
    public function testParseThrowsInvalidJsonForInvalidNumber(string $json): void
    {
        $raw = Raw::fromString($json);
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideInvalidNumber(): iterable
    {
        $values = [
            'double-minus' => '--1',
            'fraction-without-integer-part' => '.5',
            'hexadecimal' => '0x1A',
            'infinity' => 'INF',
            'leading-plus' => '+1',
            'nan' => 'NAN',
            'two-fractions' => '1.5.5',
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function testParseParsesEmptyArray(): void
    {
        $raw = Raw::fromString('[]');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ArrayNode::create();

        self::assertEquals($expected, $node);
    }

    public function testParseParsesArrayWithElements(): void
    {
        $raw = Raw::fromString('[1, "two", null, true]');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ArrayNode::create(
            Node\NumberNode::fromRaw(Raw::fromString('1')),
            Node\StringNode::fromRaw(Raw::fromString('"two"')),
            Node\NullNode::create(),
            Node\BooleanNode::fromBool(true),
        );

        self::assertEquals($expected, $node);
    }

    public function testParseParsesNestedArray(): void
    {
        $raw = Raw::fromString('[[1, 2], [3]]');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ArrayNode::create(
            Node\ArrayNode::create(
                Node\NumberNode::fromRaw(Raw::fromString('1')),
                Node\NumberNode::fromRaw(Raw::fromString('2')),
            ),
            Node\ArrayNode::create(Node\NumberNode::fromRaw(Raw::fromString('3'))),
        );

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsInvalidJsonForUnterminatedArray(): void
    {
        $raw = Raw::fromString('[1, 2');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseParsesEmptyObject(): void
    {
        $raw = Raw::fromString('{}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create();

        self::assertEquals($expected, $node);
    }

    public function testParseParsesObjectWithProperties(): void
    {
        $raw = Raw::fromString('{"name": "test", "count": 3}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"name"')),
                Node\StringNode::fromRaw(Raw::fromString('"test"')),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"count"')),
                Node\NumberNode::fromRaw(Raw::fromString('3')),
            ),
        );

        self::assertEquals($expected, $node);
    }

    public function testParseParsesObjectPreservingPropertyOrder(): void
    {
        $raw = Raw::fromString('{"b": 2, "a": 1, "c": 3}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"b"')),
                Node\NumberNode::fromRaw(Raw::fromString('2')),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"a"')),
                Node\NumberNode::fromRaw(Raw::fromString('1')),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"c"')),
                Node\NumberNode::fromRaw(Raw::fromString('3')),
            ),
        );

        self::assertEquals($expected, $node);
    }

    public function testParseParsesObjectPreservingPropertiesWithDuplicateNames(): void
    {
        $raw = Raw::fromString('{"a": 1, "a": 2}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"a"')),
                Node\NumberNode::fromRaw(Raw::fromString('1')),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"a"')),
                Node\NumberNode::fromRaw(Raw::fromString('2')),
            ),
        );

        self::assertEquals($expected, $node);
    }

    public function testParseParsesObjectPreservingRawPropertyNames(): void
    {
        $raw = Raw::fromString('{"a/b": 1, "a\\/b": 2, "\\u00e9": 3}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"a/b"')),
                Node\NumberNode::fromRaw(Raw::fromString('1')),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"a\\/b"')),
                Node\NumberNode::fromRaw(Raw::fromString('2')),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"\\u00e9"')),
                Node\NumberNode::fromRaw(Raw::fromString('3')),
            ),
        );

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsInvalidJsonForMissingColonInObject(): void
    {
        $raw = Raw::fromString('{"key" "value"}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForUnterminatedObject(): void
    {
        $raw = Raw::fromString('{"key": "value"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForNonStringKey(): void
    {
        $raw = Raw::fromString('{42: "value"}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseParsesComplexNestedStructure(): void
    {
        $raw = Raw::fromString('{"users":[{"id":1,"name":"Alice","active":true},{"id":2,"name":"Bob","active":false}],"total":2}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"users"')),
                Node\ArrayNode::create(
                    Node\ObjectNode::create(
                        Node\ObjectProperty::create(
                            Node\StringNode::fromRaw(Raw::fromString('"id"')),
                            Node\NumberNode::fromRaw(Raw::fromString('1')),
                        ),
                        Node\ObjectProperty::create(
                            Node\StringNode::fromRaw(Raw::fromString('"name"')),
                            Node\StringNode::fromRaw(Raw::fromString('"Alice"')),
                        ),
                        Node\ObjectProperty::create(
                            Node\StringNode::fromRaw(Raw::fromString('"active"')),
                            Node\BooleanNode::fromBool(true),
                        ),
                    ),
                    Node\ObjectNode::create(
                        Node\ObjectProperty::create(
                            Node\StringNode::fromRaw(Raw::fromString('"id"')),
                            Node\NumberNode::fromRaw(Raw::fromString('2')),
                        ),
                        Node\ObjectProperty::create(
                            Node\StringNode::fromRaw(Raw::fromString('"name"')),
                            Node\StringNode::fromRaw(Raw::fromString('"Bob"')),
                        ),
                        Node\ObjectProperty::create(
                            Node\StringNode::fromRaw(Raw::fromString('"active"')),
                            Node\BooleanNode::fromBool(false),
                        ),
                    ),
                ),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"total"')),
                Node\NumberNode::fromRaw(Raw::fromString('2')),
            ),
        );

        self::assertEquals($expected, $node);
    }

    /**
     * @dataProvider provideRoundTripJson
     */
    public function testParseAndPrintRoundTrip(
        string $json,
        string $expected
    ): void {
        $raw = Raw::fromString($json);
        $maximumDepth = MaximumDepth::default();
        $format = Format::compact();

        $parser = new Parser\Parser();
        $printer = new Printer();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $output = $printer->print(
            $node,
            $format,
        );

        self::assertSame($expected, $output);
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function provideRoundTripJson(): iterable
    {
        $values = [
            'null' => [
                'null',
                'null',
            ],
            'true' => [
                'true',
                'true',
            ],
            'false' => [
                'false',
                'false',
            ],
            'integer' => [
                '42',
                '42',
            ],
            'negative-integer' => [
                '-42',
                '-42',
            ],
            'float-decimal' => [
                '1.5',
                '1.5',
            ],
            'float-one-point-zero' => [
                '1.0',
                '1.0',
            ],
            'float-exponent' => [
                '1e10',
                '1e10',
            ],
            'float-negative-exponent' => [
                '1E-5',
                '1E-5',
            ],
            'string' => [
                '"hello"',
                '"hello"',
            ],
            'string-with-slash' => [
                '"a/b"',
                '"a/b"',
            ],
            'string-with-escaped-slash' => [
                '"a\\/b"',
                '"a\\/b"',
            ],
            'string-with-non-ascii' => [
                "\"\xE6\x97\xA5\xE6\x9C\xAC\"",
                "\"\xE6\x97\xA5\xE6\x9C\xAC\"",
            ],
            'string-with-unicode-escape' => [
                '"\\u00E9"',
                '"\\u00E9"',
            ],
            'array-with-strings' => [
                '["a/b","\\u00e9","\\/"]',
                '["a/b","\\u00e9","\\/"]',
            ],
            'empty-array' => [
                '[]',
                '[]',
            ],
            'empty-object' => [
                '{}',
                '{}',
            ],
            'compact-array' => [
                '[1,2,3]',
                '[1,2,3]',
            ],
            'compact-object' => [
                '{"a":1,"b":2}',
                '{"a":1,"b":2}',
            ],
            'object-with-duplicate-property-names' => [
                '{"a":1,"a":2}',
                '{"a":1,"a":2}',
            ],
            'object-with-slash-in-property-name' => [
                '{"a/b":1}',
                '{"a/b":1}',
            ],
            'object-with-escaped-slash-in-property-name' => [
                '{"a\\/b":1}',
                '{"a\\/b":1}',
            ],
            'object-with-unicode-escape-in-property-name' => [
                '{"\\u00e9":1}',
                '{"\\u00e9":1}',
            ],
            'object-with-non-ascii-property-name' => [
                "{\"\xE6\x97\xA5\":1}",
                "{\"\xE6\x97\xA5\":1}",
            ],
            'whitespace-stripped' => [
                '  { "a" : 1 }  ',
                '{"a":1}',
            ],
            'nested' => [
                '{"items":[{"id":1}],"ok":true}',
                '{"items":[{"id":1}],"ok":true}',
            ],
        ];

        foreach ($values as $key => [$json, $expected]) {
            yield $key => [
                $json,
                $expected,
            ];
        }
    }

    public function testParseThrowsInvalidJsonForInvalidBoolean(): void
    {
        $raw = Raw::fromString('tru');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForInvalidNull(): void
    {
        $raw = Raw::fromString('nul');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForInvalidCharacter(): void
    {
        $raw = Raw::fromString('&');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForTruncatedEscapeSequence(): void
    {
        $raw = Raw::fromString('"hello\\');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForMissingCommaOrBracketInArray(): void
    {
        $raw = Raw::fromString('[1 2]');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForMissingCommaOrBraceInObject(): void
    {
        $raw = Raw::fromString('{"a": 1 "b": 2}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForMissingExponentDigitAfterSign(): void
    {
        $raw = Raw::fromString('1e+');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseParsesFloatZeroPointOne(): void
    {
        $raw = Raw::fromString('0.1');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('0.1'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesNegativeFloat(): void
    {
        $raw = Raw::fromString('-3.14');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('-3.14'));

        self::assertEquals($expected, $node);
    }

    /**
     * @dataProvider provideJsonOffsetLineAndColumn
     */
    public function testParseThrowsInvalidJsonWithPositionOfError(
        string $json,
        int $offset,
        int $line,
        int $column
    ): void {
        $raw = Raw::fromString($json);
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            $position = $exception->position();

            self::assertSame($offset, $position->offset()->toInt());
            self::assertSame($line, $position->line()->toInt());
            self::assertSame($column, $position->column()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    /**
     * @return \Generator<string, array{0: string, 1: int, 2: int, 3: int}>
     */
    public static function provideJsonOffsetLineAndColumn(): iterable
    {
        $values = [
            'at-start-of-document' => [
                'x',
                0,
                1,
                1,
            ],
            'on-first-line' => [
                '[1 2]',
                3,
                1,
                4,
            ],
            'after-line-feed' => [
                "[\n1 2]",
                4,
                2,
                3,
            ],
            'after-carriage-return-and-line-feed' => [
                "[\r\n1 2]",
                5,
                2,
                3,
            ],
            'after-carriage-return' => [
                "[\r1 2]",
                4,
                2,
                3,
            ],
            'after-several-line-breaks' => [
                "[\n\r\n\r1 2]",
                7,
                4,
                3,
            ],
            'at-start-of-line' => [
                "[1,\n]",
                4,
                2,
                1,
            ],
            'after-multibyte-characters' => [
                "[\"\xC3\xA9\xE2\x82\xAC\xF0\x9F\x98\x80\" x]",
                13,
                1,
                8,
            ],
            'after-lowest-and-highest-continuation-byte' => [
                "[\"\x7F\xC2\x80\xEF\xBF\xBF\" x]",
                10,
                1,
                8,
            ],
            'after-multibyte-characters-on-previous-line' => [
                "[\"\xC3\xA9\",\nx]",
                7,
                2,
                1,
            ],
        ];

        foreach ($values as $key => [$json, $offset, $line, $column]) {
            yield $key => [
                $json,
                $offset,
                $line,
                $column,
            ];
        }
    }

    public function testParseThrowsInvalidJsonWithCorrectPositionForTrailingContent(): void
    {
        $raw = Raw::fromString('null  x');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(6, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsInvalidJsonWithCorrectPositionForInvalidEscapeSequence(): void
    {
        $raw = Raw::fromString('"ab\\x"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(4, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsInvalidJsonForHighSurrogateFollowedByNonBackslash(): void
    {
        $raw = Raw::fromString('"\\uD800a"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForHighSurrogateFollowedByTrailingBackslash(): void
    {
        $raw = Raw::fromString('"\\uD800\\');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessage('Expected low surrogate after high surrogate at line 1, column 8.');

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForHighSurrogateFollowedByTruncatedUnicodeEscape(): void
    {
        $raw = Raw::fromString('"\\uD800\\u');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessage('Expected 4 hex digits at line 1, column 10.');

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseParsesMultiDigitInteger(): void
    {
        $raw = Raw::fromString('12345');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('12345'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithMultipleDecimalDigits(): void
    {
        $raw = Raw::fromString('1.234');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1.234'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithMultipleExponentDigits(): void
    {
        $raw = Raw::fromString('1e12');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1e12'));

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsInvalidJsonForObjectWithMissingPropertyName(): void
    {
        $raw = Raw::fromString('{:1}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForObjectPropertyNameAtEndOfInput(): void
    {
        $raw = Raw::fromString('{"a":1,');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForObjectMissingColonAtEndOfInput(): void
    {
        $raw = Raw::fromString('{"a"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseParsesObjectWithWhitespaceAroundStructure(): void
    {
        $raw = Raw::fromString(' { "a" : 1 , "b" : 2 } ');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"a"')),
                Node\NumberNode::fromRaw(Raw::fromString('1')),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"b"')),
                Node\NumberNode::fromRaw(Raw::fromString('2')),
            ),
        );

        self::assertEquals($expected, $node);
    }

    public function testParseParsesArrayWithWhitespace(): void
    {
        $raw = Raw::fromString(' [ 1 , 2 , 3 ] ');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ArrayNode::create(
            Node\NumberNode::fromRaw(Raw::fromString('1')),
            Node\NumberNode::fromRaw(Raw::fromString('2')),
            Node\NumberNode::fromRaw(Raw::fromString('3')),
        );

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsInvalidJsonForArrayEndOfInputBeforeClose(): void
    {
        $raw = Raw::fromString('[1,');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForObjectEndOfInputAfterValue(): void
    {
        $raw = Raw::fromString('{"a":1');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseParsesFloatWithCapitalE(): void
    {
        $raw = Raw::fromString('5E3');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('5E3'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithExponentAndNoSign(): void
    {
        $raw = Raw::fromString('2e3');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('2e3'));

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsInvalidJsonForNumberWithInvalidDigitAfterMinus(): void
    {
        $raw = Raw::fromString('-a');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForDecimalPointFollowedByNonDigit(): void
    {
        $raw = Raw::fromString('1.a');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForExponentFollowedByNonDigit(): void
    {
        $raw = Raw::fromString('1ea');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForExponentSignFollowedByNonDigit(): void
    {
        $raw = Raw::fromString('1e-a');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsInvalidJsonForLoneSurrogateWithBackslashNotFollowedByU(): void
    {
        $raw = Raw::fromString('"\\uD800\\n"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseParsesNumberZeroFollowedByCommaInArray(): void
    {
        $raw = Raw::fromString('[0,1]');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ArrayNode::create(
            Node\NumberNode::fromRaw(Raw::fromString('0')),
            Node\NumberNode::fromRaw(Raw::fromString('1')),
        );

        self::assertEquals($expected, $node);
    }

    public function testParseParsesNegativeIntegerWithMultipleDigits(): void
    {
        $raw = Raw::fromString('-999');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('-999'));

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsInvalidJsonAtCorrectPositionForMissingPropertyNameInObject(): void
    {
        $raw = Raw::fromString('{123}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(1, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsInvalidJsonAtCorrectPositionForMissingColonInObject(): void
    {
        $raw = Raw::fromString('{"a" 1}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(5, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsInvalidJsonAtCorrectPositionForMissingCommaInObject(): void
    {
        $raw = Raw::fromString('{"a":1 "b":2}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(7, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsInvalidJsonAtCorrectPositionForMissingCommaInArray(): void
    {
        $raw = Raw::fromString('[1 2]');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(3, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsInvalidJsonAtCorrectPositionForHighSurrogateWithoutLow(): void
    {
        $raw = Raw::fromString('"\\uD800"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(7, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsInvalidJsonAtCorrectPositionForTruncatedHexQuad(): void
    {
        $raw = Raw::fromString('"\\u00"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(3, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsInvalidJsonAtCorrectPositionForInvalidNumberDigit(): void
    {
        $raw = Raw::fromString('-a');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(1, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsForHexQuadWithInvalidCharacterAtStart(): void
    {
        $raw = Raw::fromString('"\\uXXXX"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(3, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseParsesObjectWithEmptyStringKey(): void
    {
        $raw = Raw::fromString('{"":1}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create(Node\ObjectProperty::create(
            Node\StringNode::fromRaw(Raw::fromString('""')),
            Node\NumberNode::fromRaw(Raw::fromString('1')),
        ));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesArrayWithSingleElement(): void
    {
        $raw = Raw::fromString('[42]');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ArrayNode::create(Node\NumberNode::fromRaw(Raw::fromString('42')));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesObjectWithSingleProperty(): void
    {
        $raw = Raw::fromString('{"a":1}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create(Node\ObjectProperty::create(
            Node\StringNode::fromRaw(Raw::fromString('"a"')),
            Node\NumberNode::fromRaw(Raw::fromString('1')),
        ));

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsForObjectEmptyAfterOpenBrace(): void
    {
        $raw = Raw::fromString('{');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(1, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsForArrayEmptyAfterOpenBracket(): void
    {
        $raw = Raw::fromString('[');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(1, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseParsesMinPhpInt(): void
    {
        $raw = Raw::fromString((string) \PHP_INT_MIN);
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw($raw);

        self::assertEquals($expected, $node);
    }

    public function testParseParsesMaxPhpInt(): void
    {
        $raw = Raw::fromString((string) \PHP_INT_MAX);
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw($raw);

        self::assertEquals($expected, $node);
    }

    public function testParseParsesObjectWithWhitespaceBeforeClosingBrace(): void
    {
        $raw = Raw::fromString('{ }');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create();

        self::assertEquals($expected, $node);
    }

    public function testParseParsesArrayWithWhitespaceBeforeClosingBracket(): void
    {
        $raw = Raw::fromString('[ ]');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ArrayNode::create();

        self::assertEquals($expected, $node);
    }

    public function testParseParsesLiteralWithExactFalseLength(): void
    {
        $raw = Raw::fromString('[false]');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ArrayNode::create(Node\BooleanNode::fromBool(false));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithDecimalPointAndMultipleTrailingDigits(): void
    {
        $raw = Raw::fromString('3.14159');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('3.14159'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesNumberExponentWithMinusSign(): void
    {
        $raw = Raw::fromString('5e-1');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('5e-1'));

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsForObjectWithTrailingComma(): void
    {
        $raw = Raw::fromString('{"a":1,}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(7, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsForArrayWithTrailingComma(): void
    {
        $raw = Raw::fromString('[1,]');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(3, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsWithCorrectMessageForInvalidEscapeSequence(): void
    {
        $raw = Raw::fromString('"\\x"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertStringContainsString('column 3', $exception->getMessage());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsForLowSurrogateJustBelowRange(): void
    {
        $raw = Raw::fromString('"\\uD800\\uDBFF"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsForUnexpectedLowSurrogateAtExactStart(): void
    {
        $raw = Raw::fromString('"\\uDC00"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsForUnexpectedLowSurrogateAtExactEnd(): void
    {
        $raw = Raw::fromString('"\\uDFFF"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseThrowsWithCorrectPositionForInvalidLowSurrogate(): void
    {
        $raw = Raw::fromString('"\\uD800\\u0041"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(9, $exception->position()->offset()->toInt());
            self::assertStringContainsString('column 10', $exception->getMessage());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsWithCorrectPositionForUnexpectedLowSurrogate(): void
    {
        $raw = Raw::fromString('"\\uDC00"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(3, $exception->position()->offset()->toInt());
            self::assertStringContainsString('column 4', $exception->getMessage());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsForHexQuadWithPartialMatchWithoutAnchors(): void
    {
        $raw = Raw::fromString('"\\u00G0"');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    public function testParseParsesZeroInObject(): void
    {
        $raw = Raw::fromString('{"a":0}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create(Node\ObjectProperty::create(
            Node\StringNode::fromRaw(Raw::fromString('"a"')),
            Node\NumberNode::fromRaw(Raw::fromString('0')),
        ));

        self::assertEquals($expected, $node);
    }

    public function testParseHandlesNumberFollowedByClosingBrace(): void
    {
        $raw = Raw::fromString('{"x":42}');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\ObjectNode::create(Node\ObjectProperty::create(
            Node\StringNode::fromRaw(Raw::fromString('"x"')),
            Node\NumberNode::fromRaw(Raw::fromString('42')),
        ));

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsForNumberMinusOnly(): void
    {
        $raw = Raw::fromString('[-]');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(2, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseParsesFloatZeroWithDecimal(): void
    {
        $raw = Raw::fromString('0.0');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('0.0'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithExponentWithoutSign(): void
    {
        $raw = Raw::fromString('3e2');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('3e2'));

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsInvalidJsonWithUnexpectedCharacterMessageForInvalidStart(): void
    {
        $raw = Raw::fromString('&');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertStringContainsString('Unexpected character', $exception->getMessage());
            self::assertSame(0, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    /**
     * @dataProvider provideJsonWithNotPrintableBytesAndMessage
     */
    public function testParseThrowsInvalidJsonWithEscapedBytesInMessageWhenBytesAreNotPrintable(
        string $json,
        string $message
    ): void {
        $raw = Raw::fromString($json);
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessage($message);

        $parser->parse(
            $raw,
            $maximumDepth,
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function provideJsonWithNotPrintableBytesAndMessage(): iterable
    {
        $values = [
            'invalid-escape-sequence' => [
                "\"\\\xC3\xA9\"",
                'Invalid escape sequence "\\\xC3" at line 1, column 3.',
            ],
            'invalid-hex-digits' => [
                "\"\\u00\xC3\xA9\"",
                'Invalid hex digits "00\xC3\xA9" at line 1, column 4.',
            ],
            'unexpected-character-after-document' => [
                "1\x00",
                'Unexpected character "\x00" at line 1, column 2.',
            ],
            'unexpected-character-at-start-of-value' => [
                "\xC3\xA9",
                'Unexpected character "\xC3" at line 1, column 1.',
            ],
        ];

        foreach ($values as $key => [$json, $message]) {
            yield $key => [
                $json,
                $message,
            ];
        }
    }

    public function testParseParsesFloatWithNineInDecimalPart(): void
    {
        $raw = Raw::fromString('1.9');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1.9'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithNineInExponent(): void
    {
        $raw = Raw::fromString('1e9');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1e9'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithZeroInExponent(): void
    {
        $raw = Raw::fromString('1e0');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1e0'));

        self::assertEquals($expected, $node);
    }

    public function testParseParsesFloatWithNineAfterDecimalPoint(): void
    {
        $raw = Raw::fromString('0.9');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('0.9'));

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsForHighSurrogateAtExactEndOfHexQuad(): void
    {
        $raw = Raw::fromString('"\\uD800');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertStringContainsString('surrogate', $exception->getMessage());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsForTruncatedHexQuadExactlyOneShort(): void
    {
        $raw = Raw::fromString('"\\u123');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame('Expected 4 hex digits at line 1, column 4.', $exception->getMessage());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsForHexQuadExactlyAtEndOfInput(): void
    {
        $raw = Raw::fromString('"\\u004');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertStringContainsString('hex digits', $exception->getMessage());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseParsesFloatWithMultipleNinesInExponent(): void
    {
        $raw = Raw::fromString('1e99');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            $maximumDepth,
        );

        $expected = Node\NumberNode::fromRaw(Raw::fromString('1e99'));

        self::assertEquals($expected, $node);
    }

    public function testParseThrowsForObjectWithOnlyOpenBraceAndWhitespace(): void
    {
        $raw = Raw::fromString('{ ');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(2, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }

    public function testParseThrowsForArrayWithOnlyOpenBracketAndWhitespace(): void
    {
        $raw = Raw::fromString('[ ');
        $maximumDepth = MaximumDepth::default();

        $parser = new Parser\Parser();

        try {
            $parser->parse(
                $raw,
                $maximumDepth,
            );
        } catch (InvalidJson $exception) {
            self::assertSame(2, $exception->position()->offset()->toInt());

            return;
        }

        self::fail('Expected InvalidJson was not thrown.');
    }
}
