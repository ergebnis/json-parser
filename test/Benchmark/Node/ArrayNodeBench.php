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

namespace Ergebnis\Json\Parser\Test\Benchmark\Node;

use Ergebnis\Json\Parser\Node;

/**
 * @BeforeMethods("setUp")
 */
final class ArrayNodeBench
{
    private const COUNT = 100000;

    /**
     * @var list<Node\Node>
     */
    private array $elements = [];

    public function setUp(): void
    {
        for ($i = 0; self::COUNT > $i; ++$i) {
            $this->elements[] = Node\NullNode::create();
        }
    }

    public function benchAddElement(): void
    {
        $node = Node\ArrayNode::create();

        foreach ($this->elements as $element) {
            $node->addElement($element);
        }
    }

    public function benchCreate(): void
    {
        Node\ArrayNode::create(...$this->elements);
    }
}
