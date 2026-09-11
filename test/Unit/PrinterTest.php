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

use Ergebnis\Json\Parser\CyclicNodeDetected;
use Ergebnis\Json\Parser\FinalNewLine;
use Ergebnis\Json\Parser\Format;
use Ergebnis\Json\Parser\Indent;
use Ergebnis\Json\Parser\IndentSize;
use Ergebnis\Json\Parser\IndentStyle;
use Ergebnis\Json\Parser\Index;
use Ergebnis\Json\Parser\MaximumDepth;
use Ergebnis\Json\Parser\NewLine;
use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Parser;
use Ergebnis\Json\Parser\Printer;
use Ergebnis\Json\Parser\Raw;
use Ergebnis\Json\Parser\Test;
use Ergebnis\Json\Parser\UnsupportedNode;
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
    use Test\Util\Helper;

    public function testPrintThrowsUnsupportedNodeWhenNodeIsNotSupported(): void
    {
        $node = new Test\Double\Node\CustomNode();

        $format = Format::compact();

        $printer = new Printer();

        $this->expectException(UnsupportedNode::class);

        $printer->print(
            $node,
            $format,
        );
    }

    public function testPrintThrowsCyclicNodeDetectedWhenArrayNodeContainsItself(): void
    {
        $node = Node\ArrayNode::create();

        $node->addElement($node);

        $format = Format::compact();

        $printer = new Printer();

        $this->expectException(CyclicNodeDetected::class);

        $printer->print(
            $node,
            $format,
        );
    }

    public function testPrintThrowsCyclicNodeDetectedWhenObjectNodeContainsItself(): void
    {
        $node = Node\ObjectNode::create();

        $node->addProperty(Node\ObjectProperty::create(
            Node\StringNode::fromString(self::faker()->word()),
            $node,
        ));

        $format = Format::compact();

        $printer = new Printer();

        $this->expectException(CyclicNodeDetected::class);

        $printer->print(
            $node,
            $format,
        );
    }

    public function testPrintThrowsCyclicNodeDetectedWhenNodeContainsItselfThroughOtherContainers(): void
    {
        $node = Node\ArrayNode::create();

        $node->addElement(Node\ObjectNode::create(Node\ObjectProperty::create(
            Node\StringNode::fromString(self::faker()->word()),
            Node\ArrayNode::create($node),
        )));

        $format = Format::compact();

        $printer = new Printer();

        $this->expectException(CyclicNodeDetected::class);

        $printer->print(
            $node,
            $format,
        );
    }

    public function testPrintPrintsNodesAddedInSeveralPlaces(): void
    {
        $array = Node\ArrayNode::create(Node\NullNode::create());
        $object = Node\ObjectNode::create(Node\ObjectProperty::create(
            Node\StringNode::fromString('a'),
            Node\NullNode::create(),
        ));

        $node = Node\ArrayNode::create(
            $array,
            $object,
            $array,
            $object,
        );

        $format = Format::compact();

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('[[null],{"a":null},[null],{"a":null}]', $printed);
    }

    public function testPrintForgetsContainersWhenThrowingCyclicNodeDetectedForArrayNode(): void
    {
        $name = Node\StringNode::fromString(self::faker()->word());

        $object = Node\ObjectNode::create();
        $array = Node\ArrayNode::create($object);

        $object->addProperty(Node\ObjectProperty::create(
            $name,
            $array,
        ));

        $format = Format::compact();

        $printer = new Printer();

        try {
            $printer->print(
                $array,
                $format,
            );

            self::fail('Expected CyclicNodeDetected to be thrown.');
        } catch (CyclicNodeDetected $exception) {
            $object->replacePropertyAt(
                Index::fromInt(0),
                Node\ObjectProperty::create(
                    $name,
                    Node\NullNode::create(),
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
        $name = Node\StringNode::fromString(self::faker()->word());

        $array = Node\ArrayNode::create();
        $object = Node\ObjectNode::create(Node\ObjectProperty::create(
            $name,
            $array,
        ));

        $array->addElement($object);

        $format = Format::compact();

        $printer = new Printer();

        try {
            $printer->print(
                $object,
                $format,
            );

            self::fail('Expected CyclicNodeDetected to be thrown.');
        } catch (CyclicNodeDetected $exception) {
            $array->replaceElementAt(
                Index::fromInt(0),
                Node\NullNode::create(),
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

        $raw = Raw::fromString($json);

        $parser = new Parser();

        $node = $parser->parse(
            $raw,
            MaximumDepth::default(),
        );

        $format = Format::fromRaw($raw);

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($json, $printed);
    }

    public function testPrintPrintsNullNodeWhenFormatIsCompact(): void
    {
        $node = Node\NullNode::create();
        $format = Format::compact();

        $printer = new Printer();

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
        Node\BooleanNode $node,
        string $expected
    ): void {
        $format = Format::compact();

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($expected, $printed);
    }

    /**
     * @return \Generator<string, array{0: Node\BooleanNode, 1: string}>
     */
    public static function provideBooleanNodeAndExpected(): iterable
    {
        $values = [
            'true' => [
                Node\BooleanNode::fromBool(true),
                'true',
            ],
            'false' => [
                Node\BooleanNode::fromBool(false),
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
    public function testPrintPrintsNumberNodeWhenFormatIsCompact(Node\NumberNode $node): void
    {
        $format = Format::compact();

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($node->raw(), $printed);
    }

    /**
     * @return \Generator<string, array{0: Node\NumberNode}>
     */
    public static function provideNumberNode(): iterable
    {
        $values = [
            'integer' => Node\NumberNode::fromRaw(Raw::fromString('42')),
            'negative-zero' => Node\NumberNode::fromRaw(Raw::fromString('-0')),
            'fraction' => Node\NumberNode::fromRaw(Raw::fromString('1.0')),
            'exponent' => Node\NumberNode::fromRaw(Raw::fromString('1E+2')),
            'integer-beyond-int-range' => Node\NumberNode::fromRaw(Raw::fromString('99999999999999999999')),
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
    public function testPrintPrintsStringNodeWhenFormatIsCompact(Node\StringNode $node): void
    {
        $format = Format::compact();

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($node->raw(), $printed);
    }

    /**
     * @return \Generator<string, array{0: Node\StringNode}>
     */
    public static function provideStringNode(): iterable
    {
        $values = [
            'plain' => Node\StringNode::fromRaw(Raw::fromString('"hello"')),
            'escaped-quote' => Node\StringNode::fromRaw(Raw::fromString('"hello \\"world\\""')),
            'slash' => Node\StringNode::fromRaw(Raw::fromString('"a/b"')),
            'escaped-slash' => Node\StringNode::fromRaw(Raw::fromString('"a\\/b"')),
            'non-ascii' => Node\StringNode::fromRaw(Raw::fromString("\"caf\xC3\xA9\"")),
            'unicode-escape' => Node\StringNode::fromRaw(Raw::fromString('"caf\\u00E9"')),
        ];

        foreach ($values as $key => $stringNode) {
            yield $key => [
                $stringNode,
            ];
        }
    }

    public function testPrintPrintsEmptyArrayNodeWhenFormatIsCompact(): void
    {
        $node = Node\ArrayNode::create();
        $format = Format::compact();

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('[]', $printed);
    }

    public function testPrintPrintsArrayNodeWithElementsWhenFormatIsCompact(): void
    {
        $node = Node\ArrayNode::create(
            Node\NumberNode::fromRaw(Raw::fromString('1')),
            Node\StringNode::fromRaw(Raw::fromString('"two"')),
            Node\NullNode::create(),
        );

        $format = Format::compact();

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('[1,"two",null]', $printed);
    }

    public function testPrintPrintsEmptyObjectNodeWhenFormatIsCompact(): void
    {
        $node = Node\ObjectNode::create();
        $format = Format::compact();

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('{}', $printed);
    }

    public function testPrintPrintsObjectNodeWithPropertiesWhenFormatIsCompact(): void
    {
        $node = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"name"')),
                Node\StringNode::fromRaw(Raw::fromString('"test"')),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"count"')),
                Node\NumberNode::fromRaw(Raw::fromString('3')),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"a\\/b"')),
                Node\NullNode::create(),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"count"')),
                Node\NumberNode::fromRaw(Raw::fromString('4')),
            ),
        );

        $format = Format::compact();

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('{"name":"test","count":3,"a\\/b":null,"count":4}', $printed);
    }

    public function testPrintPrintsNestedStructureWhenFormatIsCompact(): void
    {
        $node = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"items"')),
                Node\ArrayNode::create(Node\ObjectNode::create(Node\ObjectProperty::create(
                    Node\StringNode::fromRaw(Raw::fromString('"id"')),
                    Node\NumberNode::fromRaw(Raw::fromString('1')),
                ))),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"active"')),
                Node\BooleanNode::fromBool(true),
            ),
        );

        $format = Format::compact();

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('{"items":[{"id":1}],"active":true}', $printed);
    }

    public function testPrintPrintsNullNodeWhenFormatHasIndentAndNewLine(): void
    {
        $node = Node\NullNode::create();

        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(4),
                IndentStyle::space(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

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
        Node\BooleanNode $node,
        string $expected
    ): void {
        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(4),
                IndentStyle::space(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($expected, $printed);
    }

    /**
     * @dataProvider provideNumberNode
     */
    public function testPrintPrintsNumberNodeWhenFormatHasIndentAndNewLine(Node\NumberNode $node): void
    {
        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(4),
                IndentStyle::space(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($node->raw(), $printed);
    }

    /**
     * @dataProvider provideStringNode
     */
    public function testPrintPrintsStringNodeWhenFormatHasIndentAndNewLine(Node\StringNode $node): void
    {
        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(4),
                IndentStyle::space(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame($node->raw(), $printed);
    }

    public function testPrintPrintsEmptyArrayNodeWhenFormatHasIndentAndNewLine(): void
    {
        $node = Node\ArrayNode::create();

        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(4),
                IndentStyle::space(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('[]', $printed);
    }

    public function testPrintPrintsArrayNodeWithElementsWhenFormatHasIndentAndNewLine(): void
    {
        $node = Node\ArrayNode::create(
            Node\NumberNode::fromRaw(Raw::fromString('1')),
            Node\StringNode::fromRaw(Raw::fromString('"two"')),
            Node\NullNode::create(),
        );

        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(4),
                IndentStyle::space(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

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
        $node = Node\ObjectNode::create();

        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(4),
                IndentStyle::space(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('{}', $printed);
    }

    public function testPrintPrintsObjectNodeWithPropertiesWhenFormatHasIndentAndNewLine(): void
    {
        $node = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"name"')),
                Node\StringNode::fromRaw(Raw::fromString('"test"')),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"a\\/b"')),
                Node\NullNode::create(),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"name"')),
                Node\NumberNode::fromRaw(Raw::fromString('4')),
            ),
        );

        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(4),
                IndentStyle::space(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

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
        $node = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"items"')),
                Node\ArrayNode::create(
                    Node\ObjectNode::create(Node\ObjectProperty::create(
                        Node\StringNode::fromRaw(Raw::fromString('"id"')),
                        Node\NumberNode::fromRaw(Raw::fromString('1')),
                    )),
                    Node\ArrayNode::create(Node\BooleanNode::fromBool(true)),
                ),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"tags"')),
                Node\ArrayNode::create(),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"meta"')),
                Node\ObjectNode::create(),
            ),
        );

        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(1),
                IndentStyle::tab(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = "{\n\t\"items\": [\n\t\t{\n\t\t\t\"id\": 1\n\t\t},\n\t\t[\n\t\t\ttrue\n\t\t]\n\t],\n\t\"tags\": [],\n\t\"meta\": {}\n}";

        self::assertSame($expected, $printed);
    }

    public function testPrintPrintsNestedStructureWhenNewLineIsCrLf(): void
    {
        $node = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"items"')),
                Node\ArrayNode::create(
                    Node\NumberNode::fromRaw(Raw::fromString('1')),
                    Node\NumberNode::fromRaw(Raw::fromString('2')),
                ),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"meta"')),
                Node\ObjectNode::create(),
            ),
        );

        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(2),
                IndentStyle::space(),
            ),
            NewLine::crLf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = "{\r\n  \"items\": [\r\n    1,\r\n    2\r\n  ],\r\n  \"meta\": {}\r\n}";

        self::assertSame($expected, $printed);
    }

    public function testPrintPrintsNestedStructureWhenIndentIsNone(): void
    {
        $node = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"items"')),
                Node\ArrayNode::create(
                    Node\NumberNode::fromRaw(Raw::fromString('1')),
                    Node\NumberNode::fromRaw(Raw::fromString('2')),
                ),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"meta"')),
                Node\ObjectNode::create(),
            ),
        );

        $format = Format::create(
            Indent::none(),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = "{\n\"items\": [\n1,\n2\n],\n\"meta\": {}\n}";

        self::assertSame($expected, $printed);
    }

    public function testPrintPrintsNestedStructureWhenIndentAndNewLineAreNone(): void
    {
        $node = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"items"')),
                Node\ArrayNode::create(
                    Node\NumberNode::fromRaw(Raw::fromString('1')),
                    Node\NumberNode::fromRaw(Raw::fromString('2')),
                ),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"meta"')),
                Node\ObjectNode::create(),
            ),
        );

        $format = Format::create(
            Indent::none(),
            NewLine::none(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        self::assertSame('{"items":[1,2],"meta":{}}', $printed);
    }

    public function testPrintPrintsSameLayoutAsJsonEncodeWithPrettyPrintWhenFormatHasIndentAndNewLine(): void
    {
        $node = Node\ObjectNode::create(
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"items"')),
                Node\ArrayNode::create(
                    Node\ObjectNode::create(
                        Node\ObjectProperty::create(
                            Node\StringNode::fromRaw(Raw::fromString('"id"')),
                            Node\NumberNode::fromRaw(Raw::fromString('1')),
                        ),
                        Node\ObjectProperty::create(
                            Node\StringNode::fromRaw(Raw::fromString('"children"')),
                            Node\ArrayNode::create(
                                Node\NumberNode::fromRaw(Raw::fromString('2')),
                                Node\NumberNode::fromRaw(Raw::fromString('3')),
                            ),
                        ),
                    ),
                    Node\NullNode::create(),
                ),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"tags"')),
                Node\ArrayNode::create(),
            ),
            Node\ObjectProperty::create(
                Node\StringNode::fromRaw(Raw::fromString('"meta"')),
                Node\ObjectNode::create(),
            ),
        );

        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(4),
                IndentStyle::space(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $printer = new Printer();

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
    public function testPrintAppendsNewLineWhenFormatHasFinalNewLine(NewLine $newLine): void
    {
        $node = Node\ArrayNode::create(Node\NullNode::create());

        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(2),
                IndentStyle::space(),
            ),
            $newLine,
            FinalNewLine::present(),
        );

        $printer = new Printer();

        $printed = $printer->print(
            $node,
            $format,
        );

        $expected = '[' . $newLine->toString() . '  null' . $newLine->toString() . ']' . $newLine->toString();

        self::assertSame($expected, $printed);
    }

    /**
     * @return \Generator<string, array{0: NewLine}>
     */
    public static function provideNewLineForFinalNewLine(): iterable
    {
        $values = [
            'cr-lf' => NewLine::crLf(),
            'lf' => NewLine::lf(),
        ];

        foreach ($values as $key => $newLine) {
            yield $key => [
                $newLine,
            ];
        }
    }
}
