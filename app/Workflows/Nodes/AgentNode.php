<?php

namespace App\Workflows\Nodes;

use App\Models\Agent;
use App\Models\Workspace;
use App\Services\AgentService;

class AgentNode extends BaseNode
{
    /**
     * Run the configured agent on the incoming prompt and return its answer.
     *
     * The agent and workspace are chosen in the node's settings; the prompt
     * arrives as the "prompt" parameter from an upstream node. The agent's
     * answer is handed downstream under the "response" key.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function execute(array $parameters): array
    {
        $agentId = $this->node->settings['agent_id'] ?? null;
        $workspaceId = $this->node->settings['workspace_id'] ?? null;

        $agent = is_int($agentId) ? Agent::find($agentId) : null;
        $workspace = is_int($workspaceId) ? Workspace::find($workspaceId) : null;

        // Without a configured agent and workspace there is nothing to run;
        // forward the parameters with an empty response rather than failing.
        if ($agent === null || $workspace === null) {
            return array_merge($parameters, ['response' => '']);
        }

        $response = app(AgentService::class)->prompt(
            $parameters['prompt'] ?? '',
            $agent,
            $workspace,
        );

        return array_merge($parameters, ['response' => $response['result'] ?? '']);
    }

    /**
     * The agent needs a prompt text to work on.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string'],
        ];
    }

    /**
     * The agent and the workspace it runs in are picked from the configured
     * lists.
     *
     * @return array<string, mixed>
     */
    public static function settingsRules(): array
    {
        return [
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
        ];
    }

    public static function key(): string
    {
        return 'agent';
    }

    public static function label(): string
    {
        return 'Ügynök';
    }

    public static function description(): string
    {
        return 'Lefuttat egy ügynököt a bejövő adaton.';
    }

    public static function icon(): string
    {
        return 'bot';
    }

    public static function color(): string
    {
        return '#6366f1';
    }
}
