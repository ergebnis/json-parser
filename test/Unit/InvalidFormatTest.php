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

use Ergebnis\Json\Parser\InvalidFormat;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\InvalidFormat
 */
final class InvalidFormatTest extends Framework\TestCase
{
    public function testFinalNewLineWithoutNewLineReturnsInvalidFormat(): void
    {
        $exception = InvalidFormat::finalNewLineWithoutNewLine();

        self::assertSame('A final new line requires a new line.', $exception->getMessage());
    }

    public function testIndentWithoutNewLineReturnsInvalidFormat(): void
    {
        $exception = InvalidFormat::indentWithoutNewLine();

        self::assertSame('An indent requires a new line.', $exception->getMessage());
    }
}
