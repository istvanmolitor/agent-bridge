<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Prompt;
use App\Models\Workspace;

class AgentService
{
    public function __construct(
        protected ClaudeCli $claudeCli,
    ) {}

    /**
     * Run a prompt through the given agent inside the given workspace.
     *
     * The agent supplies its persona and model, the workspace supplies the
     * working directory, and the call is made through the `claude` CLI.
     *
     * @param  string|Prompt  $prompt  the prompt text, or a Prompt whose content is used
     * @return array{success: bool, result: ?string, error: ?string, session_id: ?string, raw: mixed}
     */
    public function prompt(string|Prompt $prompt, Agent $agent, Workspace $workspace): array
    {
        $text = $prompt instanceof Prompt ? $prompt->content : $prompt;

        return $this->claudeCli->run($text, [
            'systemPrompt' => $agent->system_prompt,
            'promptMode' => $agent->prompt_mode,
            'model' => $agent->model,
            'workdir' => $workspace->path,
        ]);
    }
}
