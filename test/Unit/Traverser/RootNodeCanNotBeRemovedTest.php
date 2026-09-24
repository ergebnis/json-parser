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

namespace Ergebnis\Json\Parser\Test\Unit\Traverser;

use Ergebnis\Json\Parser\Test;
use Ergebnis\Json\Parser\Traverser;
use PHPUnit\Framework;

/**
 * @covers \Ergebnis\Json\Parser\Traverser\RootNodeCanNotBeRemoved
 */
final class RootNodeCanNotBeRemovedTest extends Framework\TestCase
{
    public function testByReturnsRootNodeCanNotBeRemoved(): void
    {
        $visitor = new Test\Double\Traverser\KeepingVisitor();

        $exception = Traverser\RootNodeCanNotBeRemoved::by($visitor);

        $expected = \sprintf(
            'Visitor of class "%s" removed the root node, which can not be removed.',
            Test\Double\Traverser\KeepingVisitor::class,
        );

        self::assertSame($expected, $exception->getMessage());
    }
}
