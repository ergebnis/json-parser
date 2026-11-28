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
 * @covers \Ergebnis\Json\Parser\Printer
 *
 * @uses \Ergebnis\Json\Parser\CyclicNodeDetected
 * @uses \Ergebnis\Json\Parser\FinalNewLine
 * @uses \Ergebnis\Json\Parser\Format
 * @uses \Ergebnis\Json\Parser\Indent
 * @uses \Ergebnis\Json\Parser\IndentSize
 * @uses \Ergebnis\Json\Parser\IndentStyle
 * @uses \Ergebnis\Json\Parser\Index
 * @uses \Ergebnis\Json\Parser\MaximumDepth
 * @uses \Ergebnis\Json\Parser\NewLine
 * @uses \Ergebnis\Json\Parser\Node\ArrayNode
 * @uses \Ergebnis\Json\Parser\Node\BooleanNode
 * @uses \Ergebnis\Json\Parser\Node\NullNode
 * @uses \Ergebnis\Json\Parser\Node\NumberNode
 * @uses \Ergebnis\Json\Parser\Node\ObjectNode
 * @uses \Ergebnis\Json\Parser\Node\ObjectProperty
 * @uses \Ergebnis\Json\Parser\Node\StringNode
 * @uses \Ergebnis\Json\Parser\Parser
 * @uses \Ergebnis\Json\Parser\Raw
 * @uses \Ergebnis\Json\Parser\UnsupportedNode
 */
final class PrinterTest extends Framework\TestCase
{
    use Parser\Test\Util\Helper;

    public function testPrintThrowsUnsupportedNodeWhenNodeIsNotSupported(): void
    {
        $node = new Parser\Test\Double\Node\CustomNode();

        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $this->expectException(Parser\UnsupportedNode::class);

        $printer->print(
            $node,
            $format,
        );
    }

    public function testPrintThrowsCyclicNodeDetectedWhenArrayNodeContainsItself(): void
    {
        $node = Parser\Node\ArrayNode::create();

        $node->addElement($node);

        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $this->expectException(Parser\CyclicNodeDetected::class);

        $printer->print(
            $node,
            $format,
        );
    }

    public function testPrintThrowsCyclicNodeDetectedWhenObjectNodeContainsItself(): void
    {
        $node = Parser\Node\ObjectNode::create();

        $node->addProperty(Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString(self::faker()->word()),
            $node,
        ));

        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $this->expectException(Parser\CyclicNodeDetected::class);

        $printer->print(
            $node,
            $format,
        );
    }

    public function testPrintThrowsCyclicNodeDetectedWhenNodeContainsItselfThroughOtherContainers(): void
    {
        $node = Parser\Node\ArrayNode::create();

        $node->addElement(Parser\Node\ObjectNode::create(Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString(self::faker()->word()),
            Parser\Node\ArrayNode::create($node),
        )));

        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $this->expectException(Parser\CyclicNodeDetected::class);

        $printer->print(
            $node,
            $format,
        );
    }

    public function testPrintPrintsNodesAddedInSeveralPlaces(): void
    {
        $array = Parser\Node\ArrayNode::create(Parser\Node\NullNode::create());
        $object = Parser\Node\ObjectNode::create(Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString('a'),
            Parser\Node\NullNode::create(),
        ));

        $node = Parser\Node\ArrayNode::create(
            $array,
            $object,
            $array,
            $object,
        );

        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('[[null],{"a":null},[null],{"a":null}]', $printed);
    }

    public function testPrintForgetsContainersWhenThrowingCyclicNodeDetectedForArrayNode(): void
    {
        $name = Parser\Node\StringNode::fromString(self::faker()->word());

        $object = Parser\Node\ObjectNode::create();
        $array = Parser\Node\ArrayNode::create($object);

        $object->addProperty(Parser\Node\ObjectProperty::create(
            $name,
            $array,
        ));

        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        try {
            $printer->print(
                $array,
                $format,
            );

            self::fail('Expected CyclicNodeDetected to be thrown.');
        } catch (Parser\CyclicNodeDetected $cyclicNodeDetected) {
            $object->replacePropertyAt(
                Parser\Index::fromInt(0),
                Parser\Node\ObjectProperty::create(
                    $name,
                    Parser\Node\NullNode::create(),
                ),
            );
        }

        $printed = $printer->print(
            $object,
            $format,
        );

        self::assertSame('{' . $name->raw() . ':null}', $printed);
    }

    public function testPrintForgetsContainersWhenThrowingCyclicNodeDetectedForObjectNode(): void
    {
        $name = Parser\Node\StringNode::fromString(self::faker()->word());

        $array = Parser\Node\ArrayNode::create();
        $object = Parser\Node\ObjectNode::create(Parser\Node\ObjectProperty::create(
            $name,
            $array,
        ));

        $array->addElement($object);

        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        try {
            $printer->print(
                $object,
                $format,
            );

            self::fail('Expected CyclicNodeDetected to be thrown.');
        } catch (Parser\CyclicNodeDetected $cyclicNodeDetected) {
            $array->replaceElementAt(
                Parser\Index::fromInt(0),
                Parser\Node\NullNode::create(),
            );
        }

        $printed = $printer->print(
            $array,
            $format,
        );

        self::assertSame('[null]', $printed);
    }

    public function testPrintPrintsParsedDocumentAsItWasWhenFormatIsDetectedFromRaw(): void
    {
        $json = "{\r\n\t\"name\": \"ergebnis/json-parser\",\r\n\t\"keywords\": [\r\n\t\t\"json\",\r\n\t\t\"parser\"\r\n\t],\r\n\t\"extra\": {}\r\n}\r\n";

        $raw = Parser\Raw::fromString($json);

        $parser = new Parser\Parser();

        $node = $parser->parse(
            $raw,
            Parser\MaximumDepth::default(),
        );

        $format = Parser\Format::fromRaw($raw);

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($json, $printed);
    }

    public function testPrintPrintsNullNodeWhenFormatIsCompact(): void
    {
        $node = Parser\Node\NullNode::create();
        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('null', $printed);
    }

    /**
     * @dataProvider provideBooleanNodeAndExpected
     */
    public function testPrintPrintsBooleanNodeWhenFormatIsCompact(
        Parser\Node\BooleanNode $node,
        string $expected
    ): void {
        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($expected, $printed);
    }

    /**
     * @return \Generator<string, array{0: Parser\Node\BooleanNode, 1: string}>
     */
    public static function provideBooleanNodeAndExpected(): iterable
    {
        $values = [
            'true' => [
                Parser\Node\BooleanNode::fromBool(true),
                'true',
            ],
            'false' => [
                Parser\Node\BooleanNode::fromBool(false),
                'false',
            ],
        ];

        foreach ($values as $key => [$booleanNode, $expected]) {
            yield $key => [
                $booleanNode,
                $expected,
            ];
        }
    }

    /**
     * @dataProvider provideNumberNode
     */
    public function testPrintPrintsNumberNodeWhenFormatIsCompact(Parser\Node\NumberNode $node): void
    {
        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($node->raw(), $printed);
    }

    /**
     * @return \Generator<string, array{0: Parser\Node\NumberNode}>
     */
    public static function provideNumberNode(): iterable
    {
        $values = [
            'integer' => Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('42')),
            'negative-zero' => Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('-0')),
            'fraction' => Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('1.0')),
            'exponent' => Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('1E+2')),
            'integer-beyond-int-range' => Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('99999999999999999999')),
        ];

        foreach ($values as $key => $numberNode) {
            yield $key => [
                $numberNode,
            ];
        }
    }

    /**
     * @dataProvider provideStringNode
     */
    public function testPrintPrintsStringNodeWhenFormatIsCompact(Parser\Node\StringNode $node): void
    {
        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($node->raw(), $printed);
    }

    /**
     * @return \Generator<string, array{0: Parser\Node\StringNode}>
     */
    public static function provideStringNode(): iterable
    {
        $values = [
            'plain' => Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"hello"')),
            'escaped-quote' => Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"hello \\"world\\""')),
            'slash' => Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"a/b"')),
            'escaped-slash' => Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"a\\/b"')),
            'non-ascii' => Parser\Node\StringNode::fromRaw(Parser\Raw::fromString("\"caf\xC3\xA9\"")),
            'unicode-escape' => Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"caf\\u00E9"')),
        ];

        foreach ($values as $key => $stringNode) {
            yield $key => [
                $stringNode,
            ];
        }
    }

    public function testPrintPrintsEmptyArrayNodeWhenFormatIsCompact(): void
    {
        $node = Parser\Node\ArrayNode::create();
        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('[]', $printed);
    }

    public function testPrintPrintsArrayNodeWithElementsWhenFormatIsCompact(): void
    {
        $node = Parser\Node\ArrayNode::create(
            Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('1')),
            Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"two"')),
            Parser\Node\NullNode::create(),
        );

        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('[1,"two",null]', $printed);
    }

    public function testPrintPrintsEmptyObjectNodeWhenFormatIsCompact(): void
    {
        $node = Parser\Node\ObjectNode::create();
        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('{}', $printed);
    }

    public function testPrintPrintsObjectNodeWithPropertiesWhenFormatIsCompact(): void
    {
        $node = Parser\Node\ObjectNode::create(
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"name"')),
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"test"')),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"count"')),
                Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('3')),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"a\\/b"')),
                Parser\Node\NullNode::create(),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"count"')),
                Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('4')),
            ),
        );

        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('{"name":"test","count":3,"a\\/b":null,"count":4}', $printed);
    }

    public function testPrintPrintsNestedStructureWhenFormatIsCompact(): void
    {
        $node = Parser\Node\ObjectNode::create(
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"items"')),
                Parser\Node\ArrayNode::create(Parser\Node\ObjectNode::create(Parser\Node\ObjectProperty::create(
                    Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"id"')),
                    Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('1')),
                ))),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"active"')),
                Parser\Node\BooleanNode::fromBool(true),
            ),
        );

        $format = Parser\Format::compact();

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('{"items":[{"id":1}],"active":true}', $printed);
    }

    public function testPrintPrintsNullNodeWhenFormatHasIndentAndNewLine(): void
    {
        $node = Parser\Node\NullNode::create();

        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(4),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('null', $printed);
    }

    /**
     * @dataProvider provideBooleanNodeAndExpected
     */
    public function testPrintPrintsBooleanNodeWhenFormatHasIndentAndNewLine(
        Parser\Node\BooleanNode $node,
        string $expected
    ): void {
        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(4),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($expected, $printed);
    }

    /**
     * @dataProvider provideNumberNode
     */
    public function testPrintPrintsNumberNodeWhenFormatHasIndentAndNewLine(Parser\Node\NumberNode $node): void
    {
        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(4),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($node->raw(), $printed);
    }

    /**
     * @dataProvider provideStringNode
     */
    public function testPrintPrintsStringNodeWhenFormatHasIndentAndNewLine(Parser\Node\StringNode $node): void
    {
        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(4),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($node->raw(), $printed);
    }

    public function testPrintPrintsEmptyArrayNodeWhenFormatHasIndentAndNewLine(): void
    {
        $node = Parser\Node\ArrayNode::create();

        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(4),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('[]', $printed);
    }

    public function testPrintPrintsArrayNodeWithElementsWhenFormatHasIndentAndNewLine(): void
    {
        $node = Parser\Node\ArrayNode::create(
            Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('1')),
            Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"two"')),
            Parser\Node\NullNode::create(),
        );

        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(4),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = <<<'JSON'
            [
                1,
                "two",
                null
            ]
            JSON;

        self::assertSame($expected, $printed);
    }

    public function testPrintPrintsEmptyObjectNodeWhenFormatHasIndentAndNewLine(): void
    {
        $node = Parser\Node\ObjectNode::create();

        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(4),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('{}', $printed);
    }

    public function testPrintPrintsObjectNodeWithPropertiesWhenFormatHasIndentAndNewLine(): void
    {
        $node = Parser\Node\ObjectNode::create(
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"name"')),
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"test"')),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"a\\/b"')),
                Parser\Node\NullNode::create(),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"name"')),
                Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('4')),
            ),
        );

        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(4),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = <<<'JSON'
            {
                "name": "test",
                "a\/b": null,
                "name": 4
            }
            JSON;

        self::assertSame($expected, $printed);
    }

    public function testPrintPrintsNestedStructureWhenIndentIsTab(): void
    {
        $node = Parser\Node\ObjectNode::create(
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"items"')),
                Parser\Node\ArrayNode::create(
                    Parser\Node\ObjectNode::create(Parser\Node\ObjectProperty::create(
                        Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"id"')),
                        Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('1')),
                    )),
                    Parser\Node\ArrayNode::create(Parser\Node\BooleanNode::fromBool(true)),
                ),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"tags"')),
                Parser\Node\ArrayNode::create(),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"meta"')),
                Parser\Node\ObjectNode::create(),
            ),
        );

        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(1),
                Parser\IndentStyle::tab(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = "{\n\t\"items\": [\n\t\t{\n\t\t\t\"id\": 1\n\t\t},\n\t\t[\n\t\t\ttrue\n\t\t]\n\t],\n\t\"tags\": [],\n\t\"meta\": {}\n}";

        self::assertSame($expected, $printed);
    }

    public function testPrintPrintsNestedStructureWhenNewLineIsCrLf(): void
    {
        $node = Parser\Node\ObjectNode::create(
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"items"')),
                Parser\Node\ArrayNode::create(
                    Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('1')),
                    Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('2')),
                ),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"meta"')),
                Parser\Node\ObjectNode::create(),
            ),
        );

        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(2),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::crLf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = "{\r\n  \"items\": [\r\n    1,\r\n    2\r\n  ],\r\n  \"meta\": {}\r\n}";

        self::assertSame($expected, $printed);
    }

    public function testPrintPrintsNestedStructureWhenIndentIsNone(): void
    {
        $node = Parser\Node\ObjectNode::create(
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"items"')),
                Parser\Node\ArrayNode::create(
                    Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('1')),
                    Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('2')),
                ),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"meta"')),
                Parser\Node\ObjectNode::create(),
            ),
        );

        $format = Parser\Format::create(
            Parser\Indent::none(),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = "{\n\"items\": [\n1,\n2\n],\n\"meta\": {}\n}";

        self::assertSame($expected, $printed);
    }

    public function testPrintPrintsNestedStructureWhenIndentAndNewLineAreNone(): void
    {
        $node = Parser\Node\ObjectNode::create(
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"items"')),
                Parser\Node\ArrayNode::create(
                    Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('1')),
                    Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('2')),
                ),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"meta"')),
                Parser\Node\ObjectNode::create(),
            ),
        );

        $format = Parser\Format::create(
            Parser\Indent::none(),
            Parser\NewLine::none(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('{"items":[1,2],"meta":{}}', $printed);
    }

    public function testPrintPrintsSameLayoutAsJsonEncodeWithPrettyPrintWhenFormatHasIndentAndNewLine(): void
    {
        $node = Parser\Node\ObjectNode::create(
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"items"')),
                Parser\Node\ArrayNode::create(
                    Parser\Node\ObjectNode::create(
                        Parser\Node\ObjectProperty::create(
                            Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"id"')),
                            Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('1')),
                        ),
                        Parser\Node\ObjectProperty::create(
                            Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"children"')),
                            Parser\Node\ArrayNode::create(
                                Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('2')),
                                Parser\Node\NumberNode::fromRaw(Parser\Raw::fromString('3')),
                            ),
                        ),
                    ),
                    Parser\Node\NullNode::create(),
                ),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"tags"')),
                Parser\Node\ArrayNode::create(),
            ),
            Parser\Node\ObjectProperty::create(
                Parser\Node\StringNode::fromRaw(Parser\Raw::fromString('"meta"')),
                Parser\Node\ObjectNode::create(),
            ),
        );

        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(4),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = \json_encode(
            [
                'items' => [
                    [
                        'id' => 1,
                        'children' => [
                            2,
                            3,
                        ],
                    ],
                    null,
                ],
                'tags' => [],
                'meta' => new \stdClass(),
            ],
            \JSON_PRETTY_PRINT,
        );

        self::assertSame($expected, $printed);
    }

    /**
     * @dataProvider provideNewLineForFinalNewLine
     */
    public function testPrintAppendsNewLineWhenFormatHasFinalNewLine(Parser\NewLine $newLine): void
    {
        $node = Parser\Node\ArrayNode::create(Parser\Node\NullNode::create());

        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(2),
                Parser\IndentStyle::space(),
            ),
            $newLine,
            Parser\FinalNewLine::present(),
        );

        $printer = new Parser\Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = '[' . $newLine->toString() . '  null' . $newLine->toString() . ']' . $newLine->toString();

        self::assertSame($expected, $printed);
    }

    /**
     * @return \Generator<string, array{0: Parser\NewLine}>
     */
    public static function provideNewLineForFinalNewLine(): iterable
    {
        $values = [
            'cr-lf' => Parser\NewLine::crLf(),
            'lf' => Parser\NewLine::lf(),
        ];

        foreach ($values as $key => $newLine) {
            yield $key => [
                $newLine,
            ];
        }
    }
}
