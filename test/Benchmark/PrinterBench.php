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

use Ergebnis\Json\Parser\FinalNewLine;
use Ergebnis\Json\Parser\Format;
use Ergebnis\Json\Parser\Indent;
use Ergebnis\Json\Parser\IndentSize;
use Ergebnis\Json\Parser\IndentStyle;
use Ergebnis\Json\Parser\MaximumDepth;
use Ergebnis\Json\Parser\NewLine;
use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Parser;
use Ergebnis\Json\Parser\Printer;
use Ergebnis\Json\Parser\Raw;
use Ergebnis\Json\Parser\Test;

final class PrinterBench
{
    private Format $compactFormat;
    private Node\Node $node;
    private Format $prettyFormat;
    private Printer $printer;

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

        $this->compactFormat = Format::compact();
        $this->prettyFormat = Format::create(
            Indent::create(
                IndentSize::fromInt(4),
                IndentStyle::space(),
            ),
            NewLine::lf(),
            FinalNewLine::none(),
        );
        $this->printer = new Printer();
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
