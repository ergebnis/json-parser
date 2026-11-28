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

namespace Ergebnis\Json\Parser\Test\Double\Traverser;

use Ergebnis\Json\Parser;

final class ScriptedVisitor implements Parser\Traverser\Visitor
{
    private string $name;
    private Log $log;

    /**
     * @var array<string, Parser\Traverser\EnterAction>
     */
    private array $enterActions;

    /**
     * @var array<string, Parser\Traverser\LeaveAction>
     */
    private array $leaveActions;
    private Parser\Printer $printer;

    /**
     * @param array<string, Parser\Traverser\EnterAction> $enterActions
     * @param array<string, Parser\Traverser\LeaveAction> $leaveActions
     */
    public function __construct(
        string $name,
        Log $log,
        array $enterActions,
        array $leaveActions
    ) {
        $this->name = $name;
        $this->log = $log;
        $this->enterActions = $enterActions;
        $this->leaveActions = $leaveActions;
        $this->printer = new Parser\Printer();
    }

    public function enter(
        Parser\Node\Node $node,
        Parser\Traverser\Path $path
    ): Parser\Traverser\EnterAction {
        $location = $this->location(
            $node,
            $path,
        );

        $this->log->add(\sprintf(
            '%s enters %s',
            $this->name,
            $location,
        ));

        if (\array_key_exists($location, $this->enterActions)) {
            return $this->enterActions[$location];
        }

        return Parser\Traverser\EnterAction::keep();
    }

    public function leave(
        Parser\Node\Node $node,
        Parser\Traverser\Path $path
    ): Parser\Traverser\LeaveAction {
        $location = $this->location(
            $node,
            $path,
        );

        $this->log->add(\sprintf(
            '%s leaves %s',
            $this->name,
            $location,
        ));

        if (\array_key_exists($location, $this->leaveActions)) {
            return $this->leaveActions[$location];
        }

        return Parser\Traverser\LeaveAction::keep();
    }

    private function location(
        Parser\Node\Node $node,
        Parser\Traverser\Path $path
    ): string {
        return \sprintf(
            '"%s" %s',
            $path->toJsonPointer()->toJsonString(),
            $this->printer->print(
                $node,
                Parser\Format::compact(),
            ),
        );
    }
}
