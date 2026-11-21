<?php

namespace App\Workflows\Nodes;

use App\Models\Prompt;

class PromptNode extends BaseNode
{
    /**
     * Resolve the prompt chosen in the node's settings and hand its content
     * downstream under the "prompt" key the agent node expects.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function execute(array $parameters): array
    {
        $promptId = $this->node->settings['prompt_id'] ?? null;

        $content = is_int($promptId)
            ? Prompt::find($promptId)?->content
            : null;

        return array_merge($parameters, [$this->outputKey('prompt') => $content ?? '']);
    }

    /**
     * Hands the resolved prompt text downstream under the "prompt" key.
     *
     * @return array<string, array{label: string, key: string}>
     */
    public static function outputs(): array
    {
        return ['prompt' => ['label' => 'Prompt', 'key' => 'prompt']];
    }

    /**
     * Only resolves a stored prompt: run it at once, off the queue.
     */
    public static function queued(): bool
    {
        return false;
    }

    /**
     * The prompt is picked from the configured list of prompts.
     *
     * @return array<string, mixed>
     */
    public static function settingsRules(): array
    {
        return [
            'prompt_id' => ['required', 'integer', 'exists:prompts,id'],
        ];
    }

    public static function key(): string
    {
        return 'prompt';
    }

    public static function label(): string
    {
        return 'Prompt';
    }

    public static function description(): string
    {
        return 'Egy kiválasztott prompt szövegét adja tovább.';
    }

    public static function icon(): string
    {
        return 'message-square';
    }

    public static function color(): string
    {
        return '#8b5cf6';
    }
}
