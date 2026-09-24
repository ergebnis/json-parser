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

namespace Ergebnis\Json\Parser\Test\Unit;

use Ergebnis\Json\Parser\InvalidNumber;
use Ergebnis\Json\Parser\Raw;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\InvalidNumber
 *
 * @uses \Ergebnis\Json\Parser\Bytes
 * @uses \Ergebnis\Json\Parser\Raw
 */
final class InvalidNumberTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testNotValidJsonNumberReturnsInvalidNumber(): void
    {
        $word = self::faker()->word();

        $raw = Raw::fromString($word);

        $exception = InvalidNumber::notValidJsonNumber($raw);

        $message = \sprintf(
            '"%s" is not a valid JSON number.',
            $word,
        );

        self::assertSame($message, $exception->getMessage());
    }

    /**
     * @dataProvider provideFloatAndMessage
     */
    public function testNotFiniteReturnsInvalidNumber(
        float $value,
        string $message
    ): void {
        $exception = InvalidNumber::notFinite($value);

        self::assertSame($message, $exception->getMessage());
    }

    /**
     * @return \Generator<string, array{0: float, 1: string}>
     */
    public static function provideFloatAndMessage(): iterable
    {
        $values = [
            'infinity' => [
                \INF,
                'INF can not be represented as a JSON number.',
            ],
            'negative-infinity' => [
                -\INF,
                '-INF can not be represented as a JSON number.',
            ],
            'not-a-number' => [
                \NAN,
                'NAN can not be represented as a JSON number.',
            ],
        ];

        foreach ($values as $key => [$value, $message]) {
            yield $key => [
                $value,
                $message,
            ];
        }
    }
}
