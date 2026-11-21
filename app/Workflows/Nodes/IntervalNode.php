<?php

namespace App\Workflows\Nodes;

class IntervalNode extends BaseNode
{
    /**
     * A scheduled entry point: like the start node it simply forwards the
     * initial input to its children, but it is fired automatically by the
     * scheduler at the configured interval instead of by hand.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function execute(array $parameters): array
    {
        return $parameters;
    }

    /**
     * Only forwards its input: run it at once, off the queue.
     */
    public static function queued(): bool
    {
        return false;
    }

    /**
     * The interval node is an entry point: it only has an output.
     */
    public static function hasInput(): bool
    {
        return false;
    }

    /**
     * How often the node fires (in minutes) and whether the schedule is active.
     *
     * @return array<string, mixed>
     */
    public static function settingsRules(): array
    {
        return [
            'interval_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
            'enabled' => ['boolean'],
        ];
    }

    public static function key(): string
    {
        return 'interval';
    }

    public static function label(): string
    {
        return 'Időzített indítás';
    }

    public static function description(): string
    {
        return 'A beállított időközönként automatikusan elindítja a folyamatot.';
    }

    public static function icon(): string
    {
        return 'timer';
    }

    public static function color(): string
    {
        return '#f59e0b';
    }
}
