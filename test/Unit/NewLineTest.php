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

use Ergebnis\Json\Parser\NewLine;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\NewLine
 */
final class NewLineTest extends Framework\TestCase
{
    public function testNoneReturnsNewLine(): void
    {
        $newLine = NewLine::none();

        self::assertSame('', $newLine->toString());
    }

    public function testLfReturnsNewLine(): void
    {
        $newLine = NewLine::lf();

        self::assertSame("\n", $newLine->toString());
    }

    public function testCrLfReturnsNewLine(): void
    {
        $newLine = NewLine::crLf();

        self::assertSame("\r\n", $newLine->toString());
    }

    public function testEqualsReturnsFalseWhenValueIsDifferent(): void
    {
        $one = NewLine::lf();
        $two = NewLine::crLf();

        self::assertFalse($one->equals($two));
    }

    public function testEqualsReturnsTrueWhenValueIsSame(): void
    {
        $one = NewLine::lf();
        $two = NewLine::lf();

        self::assertTrue($one->equals($two));
    }
}
