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

use Ergebnis\Json\Parser\Test;

final class JsonDecodeBench
{
    private string $json = '';

    /**
     * @param array{fixture: string} $parameters
     */
    public function setUp(array $parameters): void
    {
        $this->json = Test\Benchmark\Fixture::json($parameters['fixture']);
    }

    /**
     * @BeforeMethods("setUp")
     *
     * @ParamProviders("\Ergebnis\Json\Parser\Test\DataProvider\FixtureProvider::fixture")
     */
    public function benchJsonDecode(): void
    {
        \json_decode(
            $this->json,
            false,
            4096,
            \JSON_THROW_ON_ERROR,
        );
    }
}
