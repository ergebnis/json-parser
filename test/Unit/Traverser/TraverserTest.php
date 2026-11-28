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

use Ergebnis\Json\Parser;
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
    use Parser\Test\Util\Helper;

    public function testTraverseThrowsRootNodeCanNotBeRemovedWhenVisitorRemovesRootNodeWhenEntering(): void
    {
        $visitor = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'visitor',
            new Parser\Test\Double\Traverser\Log(),
            [
                '"" {"name":"foo"}' => Parser\Traverser\EnterAction::remove(),
            ],
            [],
        );
        $node = self::parse('{"name":"foo"}');

        $traverser = new Parser\Traverser\Traverser($visitor);

        $this->expectException(Parser\Traverser\RootNodeCanNotBeRemoved::class);

        $traverser->traverse($node);
    }

    public function testTraverseThrowsRootNodeCanNotBeRemovedWhenVisitorRemovesRootNodeWhenLeaving(): void
    {
        $visitor = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'visitor',
            new Parser\Test\Double\Traverser\Log(),
            [],
            [
                '"" {"name":"foo"}' => Parser\Traverser\LeaveAction::remove(),
            ],
        );
        $node = self::parse('{"name":"foo"}');

        $traverser = new Parser\Traverser\Traverser($visitor);

        $this->expectException(Parser\Traverser\RootNodeCanNotBeRemoved::class);

        $traverser->traverse($node);
    }

    public function testTraverseThrowsCyclicNodeDetectedWhenArrayNodeContainsItself(): void
    {
        $visitor = new Parser\Test\Double\Traverser\KeepingVisitor();
        $node = Parser\Node\ArrayNode::create();

        $node->addElement($node);

        $traverser = new Parser\Traverser\Traverser($visitor);

        $this->expectException(Parser\CyclicNodeDetected::class);

        $traverser->traverse($node);
    }

    public function testTraverseThrowsCyclicNodeDetectedWhenObjectNodeContainsItself(): void
    {
        $visitor = new Parser\Test\Double\Traverser\KeepingVisitor();
        $node = Parser\Node\ObjectNode::create();

        $node->addProperty(Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString(self::faker()->word()),
            $node,
        ));

        $traverser = new Parser\Traverser\Traverser($visitor);

        $this->expectException(Parser\CyclicNodeDetected::class);

        $traverser->traverse($node);
    }

    public function testTraverseForgetsContainersWhenThrowingCyclicNodeDetectedForArrayNode(): void
    {
        $name = Parser\Node\StringNode::fromString(self::faker()->word());

        $object = Parser\Node\ObjectNode::create();
        $array = Parser\Node\ArrayNode::create($object);

        $object->addProperty(Parser\Node\ObjectProperty::create(
            $name,
            $array,
        ));

        $traverser = new Parser\Traverser\Traverser(new Parser\Test\Double\Traverser\KeepingVisitor());

        try {
            $traverser->traverse($array);

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

        $traversed = $traverser->traverse($object);

        self::assertSame($object, $traversed);
    }

    public function testTraverseForgetsContainersWhenThrowingCyclicNodeDetectedForObjectNode(): void
    {
        $name = Parser\Node\StringNode::fromString(self::faker()->word());

        $array = Parser\Node\ArrayNode::create();
        $object = Parser\Node\ObjectNode::create(Parser\Node\ObjectProperty::create(
            $name,
            $array,
        ));

        $array->addElement($object);

        $traverser = new Parser\Traverser\Traverser(new Parser\Test\Double\Traverser\KeepingVisitor());

        try {
            $traverser->traverse($object);

            self::fail('Expected CyclicNodeDetected to be thrown.');
        } catch (Parser\CyclicNodeDetected $cyclicNodeDetected) {
            $array->replaceElementAt(
                Parser\Index::fromInt(0),
                Parser\Node\NullNode::create(),
            );
        }

        $traversed = $traverser->traverse($array);

        self::assertSame($array, $traversed);
    }

    public function testTraverseForgetsContainersWhenVisitorThrowsException(): void
    {
        $throwable = new \RuntimeException(self::faker()->sentence());

        $node = self::parse('{"a":["x"]}');

        $traverser = new Parser\Traverser\Traverser(new Parser\Test\Double\Traverser\ThrowingVisitor($throwable));

        try {
            $traverser->traverse($node);

            self::fail('Expected the throwable to be thrown.');
        } catch (\RuntimeException $runtimeException) {
            self::assertSame($throwable, $runtimeException);
        }

        $traversed = $traverser->traverse($node);

        self::assertSame($node, $traversed);
    }

    public function testTraverseAllowsVisitorToTraverseSameTreeAgainWhenEntering(): void
    {
        $node = self::parse('{"a":[1]}');

        $visitor = new Parser\Test\Double\Traverser\NestedTraversalVisitor(
            '/a/0',
            $node,
        );

        $traverser = new Parser\Traverser\Traverser($visitor);

        $visitor->setTraverser($traverser);

        $traversed = $traverser->traverse($node);

        self::assertTrue($visitor->hasTraversedAgain());
        self::assertSame($node, $traversed);
    }

    public function testTraverseEntersAndLeavesNodesInDocumentOrder(): void
    {
        $log = new Parser\Test\Double\Traverser\Log();

        $visitor = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'visitor',
            $log,
            [],
            [],
        );
        $node = self::parse('{"name":"foo","keywords":["json"]}');

        $traverser = new Parser\Traverser\Traverser($visitor);

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
        $log = new Parser\Test\Double\Traverser\Log();

        $first = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [],
            [],
        );
        $second = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('[1]');

        $traverser = new Parser\Traverser\Traverser(
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
        $replacement = Parser\Node\ArrayNode::create();

        $visitor = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'visitor',
            new Parser\Test\Double\Traverser\Log(),
            [
                '"" {}' => Parser\Traverser\EnterAction::replace($replacement),
            ],
            [],
        );
        $node = self::parse('{}');

        $traverser = new Parser\Traverser\Traverser($visitor);

        $traversed = $traverser->traverse($node);

        self::assertSame($replacement, $traversed);
    }

    public function testTraverseReplacesNodeWhenVisitorReplacesNodeWhenEntering(): void
    {
        $log = new Parser\Test\Double\Traverser\Log();

        $first = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [
                '"/keywords" ["json"]' => Parser\Traverser\EnterAction::replace(self::parse('["json","parser"]')),
            ],
            [],
        );
        $second = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('{"keywords":["json"]}');

        $traverser = new Parser\Traverser\Traverser(
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
        $log = new Parser\Test\Double\Traverser\Log();

        $first = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [],
            [
                '"/keywords" ["json"]' => Parser\Traverser\LeaveAction::replace(self::parse('["json","parser"]')),
            ],
        );
        $second = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('{"keywords":["json"]}');

        $traverser = new Parser\Traverser\Traverser(
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
        $log = new Parser\Test\Double\Traverser\Log();

        $first = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [
                '"/1" "bar"' => Parser\Traverser\EnterAction::remove(),
            ],
            [],
        );
        $second = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('["foo","bar","baz"]');

        $traverser = new Parser\Traverser\Traverser(
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
        $log = new Parser\Test\Double\Traverser\Log();

        $first = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [],
            [
                '"/1" "bar"' => Parser\Traverser\LeaveAction::remove(),
            ],
        );
        $second = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('["foo","bar","baz"]');

        $traverser = new Parser\Traverser\Traverser(
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
        $log = new Parser\Test\Double\Traverser\Log();

        $first = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [
                '"/b" "bar"' => Parser\Traverser\EnterAction::remove(),
            ],
            [],
        );
        $second = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('{"a":"foo","b":"bar","c":"baz"}');

        $traverser = new Parser\Traverser\Traverser(
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
        $log = new Parser\Test\Double\Traverser\Log();

        $first = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [],
            [
                '"/b" "bar"' => Parser\Traverser\LeaveAction::remove(),
            ],
        );
        $second = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('{"a":"foo","b":"bar","c":"baz"}');

        $traverser = new Parser\Traverser\Traverser(
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
        $log = new Parser\Test\Double\Traverser\Log();

        $first = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'first',
            $log,
            [
                '"/keywords" ["json"]' => Parser\Traverser\EnterAction::skipChildren(),
            ],
            [],
        );
        $second = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'second',
            $log,
            [],
            [],
        );
        $node = self::parse('{"keywords":["json"]}');

        $traverser = new Parser\Traverser\Traverser(
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
        $first = new Parser\Test\Double\Traverser\SkippingVisitor();
        $second = new Parser\Test\Double\Traverser\SkippingVisitor();
        $node = Parser\Node\ArrayNode::create();

        $node->addElement($node);

        $traverser = new Parser\Traverser\Traverser(
            $first,
            $second,
        );

        $traversed = $traverser->traverse($node);

        self::assertSame($node, $traversed);
    }

    public function testTraverseReplacesElementWhenVisitorReplacesElementWhenEntering(): void
    {
        $visitor = new Parser\Test\Double\Traverser\ScriptedVisitor(
            'visitor',
            new Parser\Test\Double\Traverser\Log(),
            [
                '"/1" "bar"' => Parser\Traverser\EnterAction::replace(Parser\Node\StringNode::fromString('qux')),
            ],
            [],
        );
        $node = self::parse('["foo","bar","baz"]');

        $traverser = new Parser\Traverser\Traverser($visitor);

        $traversed = $traverser->traverse($node);

        self::assertSame('["foo","qux","baz"]', self::print($traversed));
    }

    public function testTraverseKeepsObjectPropertyWhenValueIsNotReplaced(): void
    {
        $visitor = new Parser\Test\Double\Traverser\KeepingVisitor();
        $property = Parser\Node\ObjectProperty::create(
            Parser\Node\StringNode::fromString(self::faker()->word()),
            Parser\Node\NullNode::create(),
        );
        $node = Parser\Node\ObjectNode::create($property);

        $traverser = new Parser\Traverser\Traverser($visitor);

        $traverser->traverse($node);

        self::assertSame($property, $node->propertyAt(Parser\Index::fromInt(0)));
    }

    public function testTraversePassesPathsWithIndexWhenPropertiesHaveSameName(): void
    {
        $visitor = new Parser\Test\Double\Traverser\PathRecordingVisitor();
        $node = self::parse('{"a~/b":1,"a~/b":2}');

        $traverser = new Parser\Traverser\Traverser($visitor);

        $traverser->traverse($node);

        $paths = $visitor->paths();

        $first = $paths[1];
        $second = $paths[2];

        self::assertInstanceOf(Parser\Index::class, $first->index());
        self::assertSame(0, $first->index()->toInt());
        self::assertInstanceOf(Parser\Node\StringNode::class, $first->name());
        self::assertSame('a~/b', $first->name()->toString());
        self::assertSame('/a~0~1b', $first->toJsonPointer()->toJsonString());

        self::assertInstanceOf(Parser\Index::class, $second->index());
        self::assertSame(1, $second->index()->toInt());
        self::assertInstanceOf(Parser\Node\StringNode::class, $second->name());
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

        $format = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(4),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );

        $node = self::parse($json);

        $traverser = new Parser\Traverser\Traverser(new Parser\Test\Double\Traverser\SortingVisitor());

        $traversed = $traverser->traverse($node);

        $printer = new Parser\Printer();

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

    private static function parse(string $json): Parser\Node\Node
    {
        $parser = new Parser\Parser();

        return $parser->parse(
            Parser\Raw::fromString($json),
            Parser\MaximumDepth::default(),
        );
    }

    private static function print(Parser\Node\Node $node): string
    {
        $printer = new Parser\Printer();

        return $printer->print(
            $node,
            Parser\Format::compact(),
        );
    }
}
