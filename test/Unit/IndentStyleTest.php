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

use Ergebnis\Json\Parser\IndentStyle;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\IndentStyle
 */
final class IndentStyleTest extends Framework\TestCase
{
    public function testSpaceReturnsIndentStyle(): void
    {
        $indentStyle = IndentStyle::space();

        self::assertSame(' ', $indentStyle->toString());
    }

    public function testTabReturnsIndentStyle(): void
    {
        $indentStyle = IndentStyle::tab();

        self::assertSame("\t", $indentStyle->toString());
    }
}
