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

use Ergebnis\Json\Parser;

/**
 * @BeforeMethods("setUp")
 */
final class StringNodeBench
{
    private const COUNT = 100000;

    /**
     * @var list<Parser\Raw>
     */
    private array $raws = [];

    /**
     * @var list<string>
     */
    private array $values = [];

    public function setUp(): void
    {
        for ($i = 0; self::COUNT > $i; ++$i) {
            $value = \sprintf(
                'string-%d',
                $i,
            );

            $this->raws[] = Parser\Raw::fromString('"' . $value . '"');
            $this->values[] = $value;
        }
    }

    public function benchFromRaw(): void
    {
        foreach ($this->raws as $raw) {
            Parser\Node\StringNode::fromRaw($raw);
        }
    }

    public function benchFromString(): void
    {
        foreach ($this->values as $value) {
            Parser\Node\StringNode::fromString($value);
        }
    }
}
