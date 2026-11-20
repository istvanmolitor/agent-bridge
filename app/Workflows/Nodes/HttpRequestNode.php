<?php

namespace App\Workflows\Nodes;

class HttpRequestNode extends BaseNode
{
    /**
     * Perform the HTTP request and return its response for the child nodes.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function execute(array $parameters): array
    {
        // TODO: perform the configured HTTP call using $parameters and return
        // the response (status, body, ...) so it flows downstream.
        // Placeholder work so the "running" status is visible until then.
        sleep(2);

        return $parameters;
    }

    public static function key(): string
    {
        return 'http_request';
    }

    public static function label(): string
    {
        return 'HTTP kérés';
    }

    public static function description(): string
    {
        return 'HTTP hívást indít egy külső szolgáltatás felé.';
    }

    public static function icon(): string
    {
        return 'globe';
    }

    public static function color(): string
    {
        return '#f59e0b';
    }
}
