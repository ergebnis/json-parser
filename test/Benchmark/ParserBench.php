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

namespace Ergebnis\Json\Parser\Test\Benchmark;

use Ergebnis\Json\Parser;

final class ParserBench
{
    private Parser\Raw $raw;
    private Parser\MaximumDepth $maximumDepth;
    private Parser\Parser $parser;

    /**
     * @param array{fixture: string} $parameters
     */
    public function setUp(array $parameters): void
    {
        $this->raw = Parser\Raw::fromString(Fixture::json($parameters['fixture']));
        $this->maximumDepth = Parser\MaximumDepth::fromInt(4096);
        $this->parser = new Parser\Parser();
    }

    /**
     * @BeforeMethods("setUp")
     *
     * @ParamProviders("\Ergebnis\Json\Parser\Test\DataProvider\FixtureProvider::fixture")
     */
    public function benchParse(): void
    {
        $this->parser->parse(
            $this->raw,
            $this->maximumDepth,
        );
    }
}
