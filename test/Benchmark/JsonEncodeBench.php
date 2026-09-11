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

final class JsonEncodeBench
{
    /**
     * @var mixed
     */
    private $data;

    /**
     * @param array{fixture: string} $parameters
     */
    public function setUp(array $parameters): void
    {
        $this->data = \json_decode(
            Test\Benchmark\Fixture::json($parameters['fixture']),
            false,
            4096,
            \JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @BeforeMethods("setUp")
     *
     * @ParamProviders("\Ergebnis\Json\Parser\Test\DataProvider\FixtureProvider::fixture")
     */
    public function benchJsonEncode(): void
    {
        \json_encode(
            $this->data,
            \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
            4096,
        );
    }

    /**
     * @BeforeMethods("setUp")
     *
     * @ParamProviders("\Ergebnis\Json\Parser\Test\DataProvider\FixtureProvider::fixtureForPrettyFormat")
     */
    public function benchJsonEncodeWithPrettyPrint(): void
    {
        \json_encode(
            $this->data,
            \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
            4096,
        );
    }
}
