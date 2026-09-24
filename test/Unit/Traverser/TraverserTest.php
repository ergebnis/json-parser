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

namespace Ergebnis\Json\Parser\Test\Unit\Traverser;

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
use Ergebnis\Json\Parser\Traverser;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Traverser\Traverser
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
 * @uses \Ergebnis\Json\Parser\Node\NullNode
 * @uses \Ergebnis\Json\Parser\Node\NumberNode
 * @uses \Ergebnis\Json\Parser\Node\ObjectNode
 * @uses \Ergebnis\Json\Parser\Node\ObjectProperty
 * @uses \Ergebnis\Json\Parser\Node\StringNode
 * @uses \Ergebnis\Json\Parser\Parser
 * @uses \Ergebnis\Json\Parser\Printer
 * @uses \Ergebnis\Json\Parser\Raw
 * @uses \Ergebnis\Json\Parser\Traverser\EnterAction
 * @uses \Ergebnis\Json\Parser\Traverser\LeaveAction
 * @uses \Ergebnis\Json\Parser\Traverser\Path
 * @uses \Ergebnis\Json\Parser\Traverser\RootNodeCanNotBeRemoved
 */
final class TraverserTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testTraverseThrowsRootNodeCanNotBeRemovedWhenVisitorRemovesRootNodeWhenEntering(): void
    {
        $visitor = new Test\Double\Traverser\ScriptedVisitor(
            'visitor',
            new Test\Double\Traverser\Log(),
            [
                '"" {"name":"foo"}' => Traverser\EnterAction::remove(),
            ],
            [],
        );
        $node = self::parse('{"name":"foo"}');

        $traverser = new Traverser\Traverser($visitor);

        $this->expectException(Traverser\RootNodeCanNotBeRemoved::class);

        $traverser->traverse($node);
    }

    public function testTraverseThrowsRootNodeCanNotBeRemovedWhenVisitorRemovesRootNodeWhenLeaving(): void
    {
        $visitor = new Test\Double\Traverser\ScriptedVisitor(
            'visitor',
            new Test\Double\Traverser\Log(),
            [],
            [
                '"" {"name":"foo"}' => Traverser\LeaveAction::remove(),
            ],
        );
        $node = self::parse('{"name":"foo"}');

        $traverser = new Traverser\Traverser($visitor);

        $this->expectException(Traverser\RootNodeCanNotBeRemoved::class);

        $traverser->traverse($node);
    }

    public function testTraverseThrowsCyclicNodeDetectedWhenArrayNodeContainsItself(): void
    {
        $visitor = new Test\Double\Traverser\KeepingVisitor();
        $node = Node\ArrayNode::create();

        $node->addElement($node);

        $traverser = new Traverser\Traverser($visitor);

        $this->expectException(CyclicNodeDetected::class);

        $traverser->traverse($node);
    }

    public function testTraverseThrowsCyclicNodeDetectedWhenObjectNodeContainsItself(): void
    {
        $visitor = new Test\Double\Traverser\KeepingVisitor();
        $node = Node\ObjectNode::create();

        $node->addProperty(Node\ObjectProperty::create(
            Node\StringNode::fromString(self::faker()->word()),
            $node,
        ));

        $traverser = new Traverser\Traverser($visitor);

        $this->expectException(CyclicNodeDetected::class);

        $traverser->traverse($node);
    }

    public function testTraverseForgetsContainersWhenThrowingCyclicNodeDetectedForArrayNode(): void
    {
        $name = Node\StringNode::fromString(self::faker()->word());

        $object = Node\ObjectNode::create();
        $array = Node\ArrayNode::create($object);

        $object->addProperty(Node\ObjectProperty::create(
            $name,
            $array,
        ));

        $traverser = new Traverser\Traverser(new Test\Double\Traverser\KeepingVisitor());

        try {
            $traverser->traverse($array);

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

        $traversed = $traverser->traverse($object);

        self::assertSame($object, $traversed);
    }

    public function testTraverseForgetsContainersWhenThrowingCyclicNodeDetectedForObjectNode(): void
    {
        $name = Node\StringNode::fromString(self::faker()->word());

        $array = Node\ArrayNode::create();
        $object = Node\ObjectNode::create(Node\ObjectProperty::create(
            $name,
            $array,
        ));

        $array->addElement($object);

        $traverser = new Traverser\Traverser(new Test\Double\Traverser\KeepingVisitor());

        try {
            $traverser->traverse($object);

            self::fail('Expected CyclicNodeDetected to be thrown.');
        } catch (CyclicNodeDetected $exception) {
            $array->replaceElementAt(
                Index::fromInt(0),
                Node\NullNode::create(),
            );
        }

        $traversed = $traverser->traverse($array);

        self::assertSame($array, $traversed);
    }

    public function testTraverseForgetsContainersWhenVisitorThrowsException(): void
    {
        $throwable = new \RuntimeException(self::faker()->sentence());

        $node = self::parse('{"a":["x"]}');

        $traverser = new Traverser\Traverser(new Test\Double\Traverser\ThrowingVisitor($throwable));

        try {
            $traverser->traverse($node);

            self::fail('Expected the throwable to be thrown.');
        } catch (\RuntimeException $exception) {
            self::assertSame($throwable, $exception);
        }

        $traversed = $traverser->traverse($node);

        self::assertSame($node, $traversed);
    }

    public function testTraverseAllowsVisitorToTraverseSameTreeAgainWhenEntering(): void
    {
        $node = self::parse('{"a":[1]}');

        $visitor = new Test\Double\Traverser\NestedTraversalVisitor(
            '/a/0',
            $node,
        );

        $traverser = new Traverser\Traverser($visitor);

        $visitor->setTraverser($traverser);

        $traversed = $traverser->traverse($node);

        self::assertTrue($visitor->hasTraversedAgain());
        self::assertSame($node, $traversed);
    }

    public function testTraverseEntersAndLeavesNodesInDocumentOrder(): void
    {
        $log = new Test\Double\Traverser\Log();

        $visitor = new Test\Double\Traverser\ScriptedVisitor(
            'visitor',
            $log,
            [],
            [],
        );
        $node = self::parse('{"name":"foo","keywords":["json"]}');

        $traverser = new Traverser\Traverser($visitor);

        $traversed = $traverser->traverse($node);

        $expected = [
            'visitor enters "" {"name":"foo","keywords":["json"]}',
            'visitor enters "/name" "foo"',
            'visitor leaves "/name" "foo"',
            'visitor enters "/keywords" ["json"]',
            'visitor enters "/keywords/0" "json"',
            'visitor leaves "/keywords/0" "json"',
            'visitor leaves "/keywords" ["json"]',
            'visitor leaves "" {"name":"foo","keywords":["json"]}',
        ];

        self::assertSame($expected, $log->entries());
        self::assertSame($node, $traversed);
    }

    public function testTraverseCallsVisitorsInOrderOfRegistration(): void
    {
        $log = new Test\Double\Traverser\Log();

        $first = new Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [],
            [],
        );
        $second = new Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('[1]');

        $traverser = new Traverser\Traverser(
            $first,
            $second,
        );

        $traverser->traverse($node);

        $expected = [
            'first enters "" [1]',
            'second enters "" [1]',
            'first enters "/0" 1',
            'second enters "/0" 1',
            'first leaves "/0" 1',
            'second leaves "/0" 1',
            'first leaves "" [1]',
            'second leaves "" [1]',
        ];

        self::assertSame($expected, $log->entries());
    }

    public function testTraverseReturnsReplacementWhenVisitorReplacesRootNode(): void
    {
        $replacement = Node\ArrayNode::create();

        $visitor = new Test\Double\Traverser\ScriptedVisitor(
            'visitor',
            new Test\Double\Traverser\Log(),
            [
                '"" {}' => Traverser\EnterAction::replace($replacement),
            ],
            [],
        );
        $node = self::parse('{}');

        $traverser = new Traverser\Traverser($visitor);

        $traversed = $traverser->traverse($node);

        self::assertSame($replacement, $traversed);
    }

    public function testTraverseReplacesNodeWhenVisitorReplacesNodeWhenEntering(): void
    {
        $log = new Test\Double\Traverser\Log();

        $first = new Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [
                '"/keywords" ["json"]' => Traverser\EnterAction::replace(self::parse('["json","parser"]')),
            ],
            [],
        );
        $second = new Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('{"keywords":["json"]}');

        $traverser = new Traverser\Traverser(
            $first,
            $second,
        );

        $traversed = $traverser->traverse($node);

        $expected = [
            'first enters "" {"keywords":["json"]}',
            'second enters "" {"keywords":["json"]}',
            'first enters "/keywords" ["json"]',
            'second enters "/keywords" ["json","parser"]',
            'first enters "/keywords/0" "json"',
            'second enters "/keywords/0" "json"',
            'first leaves "/keywords/0" "json"',
            'second leaves "/keywords/0" "json"',
            'first enters "/keywords/1" "parser"',
            'second enters "/keywords/1" "parser"',
            'first leaves "/keywords/1" "parser"',
            'second leaves "/keywords/1" "parser"',
            'first leaves "/keywords" ["json","parser"]',
            'second leaves "/keywords" ["json","parser"]',
            'first leaves "" {"keywords":["json","parser"]}',
            'second leaves "" {"keywords":["json","parser"]}',
        ];

        self::assertSame($expected, $log->entries());
        self::assertSame($node, $traversed);
        self::assertSame('{"keywords":["json","parser"]}', self::print($traversed));
    }

    public function testTraverseReplacesNodeWhenVisitorReplacesNodeWhenLeaving(): void
    {
        $log = new Test\Double\Traverser\Log();

        $first = new Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [],
            [
                '"/keywords" ["json"]' => Traverser\LeaveAction::replace(self::parse('["json","parser"]')),
            ],
        );
        $second = new Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('{"keywords":["json"]}');

        $traverser = new Traverser\Traverser(
            $first,
            $second,
        );

        $traversed = $traverser->traverse($node);

        $expected = [
            'first enters "" {"keywords":["json"]}',
            'second enters "" {"keywords":["json"]}',
            'first enters "/keywords" ["json"]',
            'second enters "/keywords" ["json"]',
            'first enters "/keywords/0" "json"',
            'second enters "/keywords/0" "json"',
            'first leaves "/keywords/0" "json"',
            'second leaves "/keywords/0" "json"',
            'first leaves "/keywords" ["json"]',
            'second leaves "/keywords" ["json","parser"]',
            'first leaves "" {"keywords":["json","parser"]}',
            'second leaves "" {"keywords":["json","parser"]}',
        ];

        self::assertSame($expected, $log->entries());
        self::assertSame('{"keywords":["json","parser"]}', self::print($traversed));
    }

    public function testTraverseRemovesElementWhenVisitorRemovesElementWhenEntering(): void
    {
        $log = new Test\Double\Traverser\Log();

        $first = new Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [
                '"/1" "bar"' => Traverser\EnterAction::remove(),
            ],
            [],
        );
        $second = new Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('["foo","bar","baz"]');

        $traverser = new Traverser\Traverser(
            $first,
            $second,
        );

        $traversed = $traverser->traverse($node);

        $expected = [
            'first enters "" ["foo","bar","baz"]',
            'second enters "" ["foo","bar","baz"]',
            'first enters "/0" "foo"',
            'second enters "/0" "foo"',
            'first leaves "/0" "foo"',
            'second leaves "/0" "foo"',
            'first enters "/1" "bar"',
            'first enters "/1" "baz"',
            'second enters "/1" "baz"',
            'first leaves "/1" "baz"',
            'second leaves "/1" "baz"',
            'first leaves "" ["foo","baz"]',
            'second leaves "" ["foo","baz"]',
        ];

        self::assertSame($expected, $log->entries());
        self::assertSame('["foo","baz"]', self::print($traversed));
    }

    public function testTraverseRemovesElementWhenVisitorRemovesElementWhenLeaving(): void
    {
        $log = new Test\Double\Traverser\Log();

        $first = new Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [],
            [
                '"/1" "bar"' => Traverser\LeaveAction::remove(),
            ],
        );
        $second = new Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('["foo","bar","baz"]');

        $traverser = new Traverser\Traverser(
            $first,
            $second,
        );

        $traversed = $traverser->traverse($node);

        $expected = [
            'first enters "" ["foo","bar","baz"]',
            'second enters "" ["foo","bar","baz"]',
            'first enters "/0" "foo"',
            'second enters "/0" "foo"',
            'first leaves "/0" "foo"',
            'second leaves "/0" "foo"',
            'first enters "/1" "bar"',
            'second enters "/1" "bar"',
            'first leaves "/1" "bar"',
            'first enters "/1" "baz"',
            'second enters "/1" "baz"',
            'first leaves "/1" "baz"',
            'second leaves "/1" "baz"',
            'first leaves "" ["foo","baz"]',
            'second leaves "" ["foo","baz"]',
        ];

        self::assertSame($expected, $log->entries());
        self::assertSame('["foo","baz"]', self::print($traversed));
    }

    public function testTraverseRemovesPropertyWhenVisitorRemovesValueWhenEntering(): void
    {
        $log = new Test\Double\Traverser\Log();

        $first = new Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [
                '"/b" "bar"' => Traverser\EnterAction::remove(),
            ],
            [],
        );
        $second = new Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('{"a":"foo","b":"bar","c":"baz"}');

        $traverser = new Traverser\Traverser(
            $first,
            $second,
        );

        $traversed = $traverser->traverse($node);

        $expected = [
            'first enters "" {"a":"foo","b":"bar","c":"baz"}',
            'second enters "" {"a":"foo","b":"bar","c":"baz"}',
            'first enters "/a" "foo"',
            'second enters "/a" "foo"',
            'first leaves "/a" "foo"',
            'second leaves "/a" "foo"',
            'first enters "/b" "bar"',
            'first enters "/c" "baz"',
            'second enters "/c" "baz"',
            'first leaves "/c" "baz"',
            'second leaves "/c" "baz"',
            'first leaves "" {"a":"foo","c":"baz"}',
            'second leaves "" {"a":"foo","c":"baz"}',
        ];

        self::assertSame($expected, $log->entries());
        self::assertSame('{"a":"foo","c":"baz"}', self::print($traversed));
    }

    public function testTraverseRemovesPropertyWhenVisitorRemovesValueWhenLeaving(): void
    {
        $log = new Test\Double\Traverser\Log();

        $first = new Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [],
            [
                '"/b" "bar"' => Traverser\LeaveAction::remove(),
            ],
        );
        $second = new Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('{"a":"foo","b":"bar","c":"baz"}');

        $traverser = new Traverser\Traverser(
            $first,
            $second,
        );

        $traversed = $traverser->traverse($node);

        $expected = [
            'first enters "" {"a":"foo","b":"bar","c":"baz"}',
            'second enters "" {"a":"foo","b":"bar","c":"baz"}',
            'first enters "/a" "foo"',
            'second enters "/a" "foo"',
            'first leaves "/a" "foo"',
            'second leaves "/a" "foo"',
            'first enters "/b" "bar"',
            'second enters "/b" "bar"',
            'first leaves "/b" "bar"',
            'first enters "/c" "baz"',
            'second enters "/c" "baz"',
            'first leaves "/c" "baz"',
            'second leaves "/c" "baz"',
            'first leaves "" {"a":"foo","c":"baz"}',
            'second leaves "" {"a":"foo","c":"baz"}',
        ];

        self::assertSame($expected, $log->entries());
        self::assertSame('{"a":"foo","c":"baz"}', self::print($traversed));
    }

    public function testTraverseSkipsChildrenOnlyForVisitorThatSkipsChildren(): void
    {
        $log = new Test\Double\Traverser\Log();

        $first = new Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [
                '"/keywords" ["json"]' => Traverser\EnterAction::skipChildren(),
            ],
            [],
        );
        $second = new Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('{"keywords":["json"]}');

        $traverser = new Traverser\Traverser(
            $first,
            $second,
        );

        $traverser->traverse($node);

        $expected = [
            'first enters "" {"keywords":["json"]}',
            'second enters "" {"keywords":["json"]}',
            'first enters "/keywords" ["json"]',
            'second enters "/keywords" ["json"]',
            'second enters "/keywords/0" "json"',
            'second leaves "/keywords/0" "json"',
            'first leaves "/keywords" ["json"]',
            'second leaves "/keywords" ["json"]',
            'first leaves "" {"keywords":["json"]}',
            'second leaves "" {"keywords":["json"]}',
        ];

        self::assertSame($expected, $log->entries());
    }

    public function testTraverseDoesNotTraverseChildrenWhenEveryVisitorSkipsChildren(): void
    {
        $first = new Test\Double\Traverser\SkippingVisitor();
        $second = new Test\Double\Traverser\SkippingVisitor();
        $node = Node\ArrayNode::create();

        $node->addElement($node);

        $traverser = new Traverser\Traverser(
            $first,
            $second,
        );

        $traversed = $traverser->traverse($node);

        self::assertSame($node, $traversed);
    }

    public function testTraverseReplacesElementWhenVisitorReplacesElementWhenEntering(): void
    {
        $visitor = new Test\Double\Traverser\ScriptedVisitor(
            'visitor',
            new Test\Double\Traverser\Log(),
            [
                '"/1" "bar"' => Traverser\EnterAction::replace(Node\StringNode::fromString('qux')),
            ],
            [],
        );
        $node = self::parse('["foo","bar","baz"]');

        $traverser = new Traverser\Traverser($visitor);

        $traversed = $traverser->traverse($node);

        self::assertSame('["foo","qux","baz"]', self::print($traversed));
    }

    public function testTraverseKeepsObjectPropertyWhenValueIsNotReplaced(): void
    {
        $visitor = new Test\Double\Traverser\KeepingVisitor();
        $property = Node\ObjectProperty::create(
            Node\StringNode::fromString(self::faker()->word()),
            Node\NullNode::create(),
        );
        $node = Node\ObjectNode::create($property);

        $traverser = new Traverser\Traverser($visitor);

        $traverser->traverse($node);

        self::assertSame($property, $node->propertyAt(Index::fromInt(0)));
    }

    public function testTraversePassesPathsWithIndexWhenPropertiesHaveSameName(): void
    {
        $visitor = new Test\Double\Traverser\PathRecordingVisitor();
        $node = self::parse('{"a~/b":1,"a~/b":2}');

        $traverser = new Traverser\Traverser($visitor);

        $traverser->traverse($node);

        $paths = $visitor->paths();

        $first = $paths[1];
        $second = $paths[2];

        self::assertInstanceOf(Index::class, $first->index());
        self::assertSame(0, $first->index()->toInt());
        self::assertInstanceOf(Node\StringNode::class, $first->name());
        self::assertSame('a~/b', $first->name()->toString());
        self::assertSame('/a~0~1b', $first->toJsonPointer()->toJsonString());

        self::assertInstanceOf(Index::class, $second->index());
        self::assertSame(1, $second->index()->toInt());
        self::assertInstanceOf(Node\StringNode::class, $second->name());
        self::assertSame('a~/b', $second->name()->toString());
        self::assertSame('/a~0~1b', $second->toJsonPointer()->toJsonString());
    }

    public function testTraverseAllowsVisitorToSortPropertiesByName(): void
    {
        $json = <<<'EOD'
            {
                "\u0073uggest": {},
                "require": {
                    "php": "~7.4.0 || ~8.0.0",
                    "ext-json": "*"
                },
                "name": "ergebnis/json-parser",
                "extra": {
                    "branch-alias": {
                        "dev-main": "1.0-dev"
                    },
                    "b": 1E+3,
                    "a": 1.0,
                    "b": 2
                },
                "\u0061uthors": [
                    {
                        "role": "Developer",
                        "name": "Andreas M\u00f6ller"
                    }
                ]
            }
            EOD;

        $format = Format::create(
            Indent::create(
                IndentSize::fromInt(4),
                IndentStyle::space(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );

        $node = self::parse($json);

        $traverser = new Traverser\Traverser(new Test\Double\Traverser\SortingVisitor());

        $traversed = $traverser->traverse($node);

        $printer = new Printer();

        $printed = $printer->print(
            $traversed,
            $format,
        );

        $expected = <<<'EOD'
            {
                "\u0061uthors": [
                    {
                        "name": "Andreas M\u00f6ller",
                        "role": "Developer"
                    }
                ],
                "extra": {
                    "a": 1.0,
                    "b": 1E+3,
                    "b": 2,
                    "branch-alias": {
                        "dev-main": "1.0-dev"
                    }
                },
                "name": "ergebnis/json-parser",
                "require": {
                    "ext-json": "*",
                    "php": "~7.4.0 || ~8.0.0"
                },
                "\u0073uggest": {}
            }
            EOD;

        self::assertSame($expected, $printed);
    }

    private static function parse(string $json): Node\Node
    {
        $parser = new Parser();

        return $parser->parse(
            Raw::fromString($json),
            MaximumDepth::default(),
        );
    }

    private static function print(Node\Node $node): string
    {
        $printer = new Printer();

        return $printer->print(
            $node,
            Format::compact(),
        );
    }
}
