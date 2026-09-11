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

use Ergebnis\Json\Parser\FinalNewLine;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\FinalNewLine
 */
final class FinalNewLineTest extends Framework\TestCase
{
    public function testEqualsReturnsFalseWhenValueIsDifferent(): void
    {
        $one = FinalNewLine::none();
        $two = FinalNewLine::present();

        self::assertFalse($one->equals($two));
    }

    /**
     * @dataProvider provideSameFinalNewLines
     */
    public function testEqualsReturnsTrueWhenValueIsSame(
        FinalNewLine $one,
        FinalNewLine $two
    ): void {
        self::assertTrue($one->equals($two));
    }

    /**
     * @return \Generator<string, array{0: FinalNewLine, 1: FinalNewLine}>
     */
    public static function provideSameFinalNewLines(): iterable
    {
        $values = [
            'none' => [
                FinalNewLine::none(),
                FinalNewLine::none(),
            ],
            'present' => [
                FinalNewLine::present(),
                FinalNewLine::present(),
            ],
        ];

        foreach ($values as $key => [$one, $two]) {
            yield $key => [
                $one,
                $two,
            ];
        }
    }
}
