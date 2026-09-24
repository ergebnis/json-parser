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

namespace Ergebnis\Json\Parser\Test\DataProvider;

use Ergebnis\Json\Parser\Test;

final class FixtureProvider
{
    /**
     * @return \Generator<string, array{fixture: string}>
     */
    public static function fixture(): iterable
    {
        foreach (Test\Benchmark\Fixture::names() as $name) {
            yield $name => [
                'fixture' => $name,
            ];
        }
    }

    /**
     * @return \Generator<string, array{fixture: string}>
     */
    public static function fixtureForPrettyFormat(): iterable
    {
        foreach (Test\Benchmark\Fixture::names() as $name) {
            /**
             * The indentation of deep-nesting grows with every level: printed with an indent of 4 spaces, the document has about 67 MB of indentation.
             */
            if ('deep-nesting' === $name) {
                continue;
            }

            yield $name => [
                'fixture' => $name,
            ];
        }
    }
}
