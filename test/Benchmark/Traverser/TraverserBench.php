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

use Ergebnis\Json\Parser;

final class TraverserBench
{
    private Parser\Node\Node $node;
    private Parser\Traverser\Traverser $traverserWithJsonPointerVisitor;
    private Parser\Traverser\Traverser $traverserWithKeepingVisitor;

    /**
     * @param array{fixture: string} $parameters
     */
    public function setUp(array $parameters): void
    {
        $parser = new Parser\Parser();

        $this->node = $parser->parse(
            Parser\Raw::fromString(Parser\Test\Benchmark\Fixture::json($parameters['fixture'])),
            Parser\MaximumDepth::fromInt(4096),
        );

        $this->traverserWithJsonPointerVisitor = new Parser\Traverser\Traverser(new Parser\Test\Double\Traverser\JsonPointerVisitor());
        $this->traverserWithKeepingVisitor = new Parser\Traverser\Traverser(new Parser\Test\Double\Traverser\KeepingVisitor());
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
