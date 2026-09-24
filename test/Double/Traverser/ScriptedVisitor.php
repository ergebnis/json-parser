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

use Ergebnis\Json\Parser\Format;
use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Printer;
use Ergebnis\Json\Parser\Traverser;

final class ScriptedVisitor implements Traverser\Visitor
{
    private string $name;
    private Log $log;

    /**
     * @var array<string, Traverser\EnterAction>
     */
    private array $enterActions;

    /**
     * @var array<string, Traverser\LeaveAction>
     */
    private array $leaveActions;
    private Printer $printer;

    /**
     * @param array<string, Traverser\EnterAction> $enterActions
     * @param array<string, Traverser\LeaveAction> $leaveActions
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
        $this->printer = new Printer();
    }

    public function enter(
        Node\Node $node,
        Traverser\Path $path
    ): Traverser\EnterAction {
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

        return Traverser\EnterAction::keep();
    }

    public function leave(
        Node\Node $node,
        Traverser\Path $path
    ): Traverser\LeaveAction {
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

        return Traverser\LeaveAction::keep();
    }

    private function location(
        Node\Node $node,
        Traverser\Path $path
    ): string {
        return \sprintf(
            '"%s" %s',
            $path->toJsonPointer()->toJsonString(),
            $this->printer->print(
                $node,
                Format::compact(),
            ),
        );
    }
}
