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
 * @see https://datatracker.ietf.org/doc/html/rfc8259#section-10
 */
final class Printer
{
    private string $newLine = '';
    private string $indent = '';
    private string $nameSeparator = '';

    /**
     * @var array<int, Node\ArrayNode|Node\ObjectNode>
     */
    private array $containers = [];

    /**
     * @throws CyclicNodeDetected
     * @throws UnsupportedNode
     */
    public function print(
        Node\Node $node,
        Format $format
    ): string {
        $nameSeparator = ': ';

        if ($format->newLine()->equals(NewLine::none())) {
            $nameSeparator = ':';
        }

        $this->newLine = $format->newLine()->toString();
        $this->indent = $format->indent()->toString();
        $this->nameSeparator = $nameSeparator;
        $this->containers = [];

        $printed = $this->printNode($node);

        if ($format->finalNewLine()->equals(FinalNewLine::present())) {
            $printed .= $this->newLine;
        }

        return $printed;
    }

    /**
     * @throws CyclicNodeDetected
     * @throws UnsupportedNode
     */
    private function printNode(Node\Node $node): string
    {
        if ($node instanceof Node\StringNode) {
            return $node->raw();
        }

        if ($node instanceof Node\NumberNode) {
            return $node->raw();
        }

        if ($node instanceof Node\ObjectNode) {
            return $this->printObjectNode($node);
        }

        if ($node instanceof Node\ArrayNode) {
            return $this->printArrayNode($node);
        }

        if ($node instanceof Node\BooleanNode) {
            if ($node->toBool()) {
                return 'true';
            }

            return 'false';
        }

        if ($node instanceof Node\NullNode) {
            return 'null';
        }

        throw UnsupportedNode::fromNode($node);
    }

    /**
     * @throws CyclicNodeDetected
     */
    private function printArrayNode(Node\ArrayNode $node): string
    {
        $elements = $node->elements();

        if ([] === $elements) {
            return '[]';
        }

        $id = \spl_object_id($node);

        if (\array_key_exists($id, $this->containers)) {
            throw CyclicNodeDetected::fromNode($node);
        }

        $closingIndent = \str_repeat(
            $this->indent,
            \count($this->containers),
        );

        $this->containers[$id] = $node;

        $elementIndent = \str_repeat(
            $this->indent,
            \count($this->containers),
        );

        $printedElements = [];

        foreach ($elements as $element) {
            $printedElements[] = $elementIndent . $this->printNode($element);
        }

        unset($this->containers[$id]);

        return '[' . $this->newLine . \implode(',' . $this->newLine, $printedElements) . $this->newLine . $closingIndent . ']';
    }

    /**
     * @throws CyclicNodeDetected
     */
    private function printObjectNode(Node\ObjectNode $node): string
    {
        $properties = $node->properties();

        if ([] === $properties) {
            return '{}';
        }

        $id = \spl_object_id($node);

        if (\array_key_exists($id, $this->containers)) {
            throw CyclicNodeDetected::fromNode($node);
        }

        $closingIndent = \str_repeat(
            $this->indent,
            \count($this->containers),
        );

        $this->containers[$id] = $node;

        $propertyIndent = \str_repeat(
            $this->indent,
            \count($this->containers),
        );

        $printedProperties = [];

        foreach ($properties as $property) {
            $printedProperties[] = $propertyIndent . $property->name()->raw() . $this->nameSeparator . $this->printNode($property->value());
        }

        unset($this->containers[$id]);

        return '{' . $this->newLine . \implode(',' . $this->newLine, $printedProperties) . $this->newLine . $closingIndent . '}';
    }
}
