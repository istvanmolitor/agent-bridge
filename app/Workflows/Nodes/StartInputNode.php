<?php

namespace App\Workflows\Nodes;

class StartInputNode extends BaseNode
{
    /**
     * Entry point that hands the text typed on the node downstream under the
     * "prompt" key. The text arrives in the parameters from the run request,
     * falling back to any value stored on the node's settings.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function execute(array $parameters): array
    {
        $text = (string) ($parameters['text'] ?? $this->node->settings['text'] ?? '');

        unset($parameters['text']);

        return array_merge($parameters, [$this->outputKey('text') => $text]);
    }

    /**
     * Only forwards the typed text: run it at once, off the queue.
     */
    public static function queued(): bool
    {
        return false;
    }

    /**
     * The start node is an entry point: it only has an output.
     */
    public static function hasInput(): bool
    {
        return false;
    }

    /**
     * Hands the typed text downstream under the "text" key.
     *
     * @return array<string, array{label: string, key: string}>
     */
    public static function outputs(): array
    {
        return ['text' => ['label' => 'Szöveg', 'key' => 'text']];
    }

    public static function key(): string
    {
        return 'start_input';
    }

    public static function label(): string
    {
        return 'Szöveges indítás';
    }

    public static function description(): string
    {
        return 'Beírt szöveggel indítja a folyamatot.';
    }

    public static function icon(): string
    {
        return 'text-cursor-input';
    }

    public static function color(): string
    {
        return '#10b981';
    }
}
