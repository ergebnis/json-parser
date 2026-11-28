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

namespace Ergebnis\Json\Parser\Traverser;

use Ergebnis\Json\Parser;

final class Traverser
{
    /**
     * @var array<int, Visitor>
     */
    private array $visitors;

    /**
     * @var array<int, Parser\Node\ArrayNode|Parser\Node\ObjectNode>
     */
    private array $containers = [];
    private bool $removed = false;

    public function __construct(Visitor ...$visitors)
    {
        $this->visitors = $visitors;
    }

    public function traverse(Parser\Node\Node $node): Parser\Node\Node
    {
        $containers = $this->containers;

        $this->containers = [];

        try {
            $traversed = $this->traverseNode(
                $node,
                Path::root(),
                $this->visitors,
            );
        } finally {
            $this->containers = $containers;
        }

        return $traversed;
    }

    /**
     * @param array<int, Visitor> $visitors
     */
    private function traverseNode(
        Parser\Node\Node $node,
        Path $path,
        array $visitors
    ): Parser\Node\Node {
        $visitorsOfChildren = $visitors;

        foreach ($visitors as $key => $visitor) {
            $action = $visitor->enter(
                $node,
                $path,
            );

            if ($action->isRemove()) {
                $this->markAsRemoved(
                    $path,
                    $visitor,
                );

                return $node;
            }

            $replacement = $action->replacement();

            if ($replacement instanceof Parser\Node\Node) {
                $node = $replacement;
            }

            if ($action->isSkipChildren()) {
                unset($visitorsOfChildren[$key]);
            }
        }

        if ([] !== $visitorsOfChildren) {
            if ($node instanceof Parser\Node\ArrayNode) {
                $this->traverseElements(
                    $node,
                    $path,
                    $visitorsOfChildren,
                );
            }

            if ($node instanceof Parser\Node\ObjectNode) {
                $this->traverseProperties(
                    $node,
                    $path,
                    $visitorsOfChildren,
                );
            }
        }

        foreach ($visitors as $visitor) {
            $action = $visitor->leave(
                $node,
                $path,
            );

            if ($action->isRemove()) {
                $this->markAsRemoved(
                    $path,
                    $visitor,
                );

                return $node;
            }

            $replacement = $action->replacement();

            if ($replacement instanceof Parser\Node\Node) {
                $node = $replacement;
            }
        }

        return $node;
    }

    /**
     * @throws RootNodeCanNotBeRemoved
     */
    private function markAsRemoved(
        Path $path,
        Visitor $visitor
    ): void {
        if (!$path->index() instanceof Parser\Index) {
            throw RootNodeCanNotBeRemoved::by($visitor);
        }

        $this->removed = true;
    }

    /**
     * @param array<int, Visitor> $visitors
     *
     * @throws Parser\CyclicNodeDetected
     */
    private function traverseElements(
        Parser\Node\ArrayNode $node,
        Path $path,
        array $visitors
    ): void {
        $id = \spl_object_id($node);

        if (\array_key_exists($id, $this->containers)) {
            throw Parser\CyclicNodeDetected::fromNode($node);
        }

        $this->containers[$id] = $node;

        $position = 0;

        while ($node->count() > $position) {
            $index = Parser\Index::fromInt($position);

            $element = $this->traverseNode(
                $node->elementAt($index),
                $path->element($index),
                $visitors,
            );

            if ($this->removed) {
                $this->removed = false;

                $node->removeElementAt($index);

                continue;
            }

            $node->replaceElementAt(
                $index,
                $element,
            );

            ++$position;
        }

        unset($this->containers[$id]);
    }

    /**
     * @param array<int, Visitor> $visitors
     *
     * @throws Parser\CyclicNodeDetected
     */
    private function traverseProperties(
        Parser\Node\ObjectNode $node,
        Path $path,
        array $visitors
    ): void {
        $id = \spl_object_id($node);

        if (\array_key_exists($id, $this->containers)) {
            throw Parser\CyclicNodeDetected::fromNode($node);
        }

        $this->containers[$id] = $node;

        $position = 0;

        while ($node->count() > $position) {
            $index = Parser\Index::fromInt($position);
            $property = $node->propertyAt($index);

            $value = $this->traverseNode(
                $property->value(),
                $path->property(
                    $index,
                    $property->name(),
                ),
                $visitors,
            );

            if ($this->removed) {
                $this->removed = false;

                $node->removePropertyAt($index);

                continue;
            }

            if ($property->value() !== $value) {
                $node->replacePropertyAt(
                    $index,
                    Parser\Node\ObjectProperty::create(
                        $property->name(),
                        $value,
                    ),
                );
            }

            ++$position;
        }

        unset($this->containers[$id]);
    }
}
