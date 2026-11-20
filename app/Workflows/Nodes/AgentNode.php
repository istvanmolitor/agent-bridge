<?php

namespace App\Workflows\Nodes;

class AgentNode extends BaseNode
{
    /**
     * Run the agent on the incoming data and return its output.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function execute(array $parameters): array
    {
        // TODO: dispatch the configured agent with $parameters as its input.
        // The agent's response should be returned here so it flows downstream.
        // Placeholder work so the "running" status is visible until then.
        sleep(2);

        return $parameters;
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
