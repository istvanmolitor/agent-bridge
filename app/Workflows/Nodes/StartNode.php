<?php

namespace App\Workflows\Nodes;

class StartNode extends BaseNode
{
    /**
     * The entry point simply forwards the initial input to its children.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function execute(array $parameters): array
    {
        return $parameters;
    }

    /**
     * The entry point does no heavy work: run it at once, off the queue.
     */
    public static function queued(): bool
    {
        return false;
    }

    public static function key(): string
    {
        return 'start';
    }

    public static function label(): string
    {
        return 'Indítás';
    }

    public static function description(): string
    {
        return 'A folyamat belépési pontja.';
    }

    public static function icon(): string
    {
        return 'play';
    }

    public static function color(): string
    {
        return '#10b981';
    }

    /**
     * The start node is the entry point: it only has an output.
     */
    public static function hasInput(): bool
    {
        return false;
    }
}
