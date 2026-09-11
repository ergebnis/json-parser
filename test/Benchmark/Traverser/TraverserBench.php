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

namespace Ergebnis\Json\Parser\Test\Benchmark\Traverser;

use Ergebnis\Json\Parser\MaximumDepth;
use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Parser;
use Ergebnis\Json\Parser\Raw;
use Ergebnis\Json\Parser\Test;
use Ergebnis\Json\Parser\Traverser;

final class TraverserBench
{
    private Node\Node $node;
    private Traverser\Traverser $traverserWithJsonPointerVisitor;
    private Traverser\Traverser $traverserWithKeepingVisitor;

    /**
     * @param array{fixture: string} $parameters
     */
    public function setUp(array $parameters): void
    {
        $parser = new Parser();

        $this->node = $parser->parse(
            Raw::fromString(Test\Benchmark\Fixture::json($parameters['fixture'])),
            MaximumDepth::fromInt(4096),
        );

        $this->traverserWithJsonPointerVisitor = new Traverser\Traverser(new Test\Double\Traverser\JsonPointerVisitor());
        $this->traverserWithKeepingVisitor = new Traverser\Traverser(new Test\Double\Traverser\KeepingVisitor());
    }

    /**
     * @BeforeMethods("setUp")
     *
     * @ParamProviders("\Ergebnis\Json\Parser\Test\DataProvider\FixtureProvider::fixture")
     */
    public function benchTraverseWithJsonPointerVisitor(): void
    {
        $this->traverserWithJsonPointerVisitor->traverse($this->node);
    }

    /**
     * @BeforeMethods("setUp")
     *
     * @ParamProviders("\Ergebnis\Json\Parser\Test\DataProvider\FixtureProvider::fixture")
     */
    public function benchTraverseWithKeepingVisitor(): void
    {
        $this->traverserWithKeepingVisitor->traverse($this->node);
    }
}
