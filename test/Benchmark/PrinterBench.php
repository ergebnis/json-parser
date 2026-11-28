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

final class PrinterBench
{
    private Parser\Format $compactFormat;
    private Parser\Node\Node $node;
    private Parser\Format $prettyFormat;
    private Parser\Printer $printer;

    /**
     * @param array{fixture: string} $parameters
     */
    public function setUp(array $parameters): void
    {
        $parser = new Parser\Parser();

        $this->node = $parser->parse(
            Parser\Raw::fromString(Fixture::json($parameters['fixture'])),
            Parser\MaximumDepth::fromInt(4096),
        );

        $this->compactFormat = Parser\Format::compact();
        $this->prettyFormat = Parser\Format::create(
            Parser\Indent::create(
                Parser\IndentSize::fromInt(4),
                Parser\IndentStyle::space(),
            ),
            Parser\NewLine::lf(),
            Parser\FinalNewLine::none(),
        );
        $this->printer = new Parser\Printer();
    }

    /**
     * @BeforeMethods("setUp")
     *
     * @ParamProviders("\Ergebnis\Json\Parser\Test\DataProvider\FixtureProvider::fixture")
     */
    public function benchPrintWithCompactFormat(): void
    {
        $this->printer->print(
            $this->node,
            $this->compactFormat,
        );
    }

    /**
     * @BeforeMethods("setUp")
     *
     * @ParamProviders("\Ergebnis\Json\Parser\Test\DataProvider\FixtureProvider::fixtureForPrettyFormat")
     */
    public function benchPrintWithPrettyFormat(): void
    {
        $this->printer->print(
            $this->node,
            $this->prettyFormat,
        );
    }
}
