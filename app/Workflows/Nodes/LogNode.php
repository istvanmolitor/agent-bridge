<?php

namespace App\Workflows\Nodes;

use Illuminate\Support\Facades\Log;

class LogNode extends BaseNode
{
    /**
     * Write the parameters it received to the log file, then forward them
     * unchanged to its children.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function execute(array $parameters): array
    {
        // Writing to the log file is opt-out: it happens unless the checkbox
        // was explicitly cleared.
        if ($this->node->settings['to_file'] ?? true) {
            Log::info('Workflow log node', [
                'workflow_id' => $this->node->workflow_id,
                'node_id' => $this->node->id,
                'node_name' => $this->node->name,
                'parameters' => $parameters,
            ]);
        }

        return $parameters;
    }

    /**
     * Whether the received parameters are written to the log file.
     *
     * @return array<string, mixed>
     */
    public static function settingsRules(): array
    {
        return [
            'to_file' => ['boolean'],
        ];
    }

    /**
     * Only records its input: run it at once, off the queue.
     */
    public static function queued(): bool
    {
        return false;
    }

    public static function key(): string
    {
        return 'log';
    }

    public static function label(): string
    {
        return 'Napló';
    }

    public static function description(): string
    {
        return 'Megjeleníti a kapott paramétereket.';
    }

    public static function icon(): string
    {
        return 'scroll-text';
    }

    public static function color(): string
    {
        return '#64748b';
    }
}
