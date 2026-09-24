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

use Ergebnis\Json\Parser\InvalidString;
use Ergebnis\Json\Parser\Raw;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\InvalidString
 *
 * @uses \Ergebnis\Json\Parser\Bytes
 * @uses \Ergebnis\Json\Parser\Raw
 */
final class InvalidStringTest extends Framework\TestCase
{
    use Test\Util\Helper;

    public function testNotValidJsonStringReturnsInvalidString(): void
    {
        $word = self::faker()->word();

        $raw = Raw::fromString('"' . $word . "\x00");

        $exception = InvalidString::notValidJsonString($raw);

        $message = \sprintf(
            '""%s\x00" is not a valid JSON string.',
            $word,
        );

        self::assertSame($message, $exception->getMessage());
    }

    public function testNotValidUtf8ReturnsInvalidString(): void
    {
        $word = self::faker()->word();

        $exception = InvalidString::notValidUtf8($word . "\xC3");

        $message = \sprintf(
            '"%s\xC3" is not valid UTF-8.',
            $word,
        );

        self::assertSame($message, $exception->getMessage());
    }
}
