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
use Ergebnis\Json\Parser\Raw;

/**
 * @BeforeMethods("setUp")
 */
final class NumberNodeBench
{
    private const COUNT = 100000;

    /**
     * @var list<Raw>
     */
    private array $raws = [];

    /**
     * @var list<int>
     */
    private array $ints = [];

    /**
     * @var list<float>
     */
    private array $floats = [];

    public function setUp(): void
    {
        for ($i = 0; self::COUNT > $i; ++$i) {
            $this->raws[] = Raw::fromString(\sprintf(
                '%d.%d',
                $i,
                $i,
            ));

            $this->ints[] = $i;
            $this->floats[] = $i / 7;
        }
    }

    public function benchFromRaw(): void
    {
        foreach ($this->raws as $raw) {
            Node\NumberNode::fromRaw($raw);
        }
    }

    public function benchFromInt(): void
    {
        foreach ($this->ints as $int) {
            Node\NumberNode::fromInt($int);
        }
    }

    public function benchFromFloat(): void
    {
        foreach ($this->floats as $float) {
            Node\NumberNode::fromFloat($float);
        }
    }
}
