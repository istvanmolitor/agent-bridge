<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromptRequest;
use App\Models\ApiKey;
use App\Services\ClaudeCli;
use Illuminate\Http\JsonResponse;

class PromptController extends Controller
{
    public function store(PromptRequest $request, ClaudeCli $claudeCli): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->attributes->get('apiKey');
        $session = $apiKey->agentSession;
        $agent = $session->agent;

        $prompt = $request->validated('prompt');

        $response = $claudeCli->run($prompt, [
            'systemPrompt' => $agent->system_prompt,
            'promptMode' => $agent->prompt_mode,
            'model' => $agent->model,
            'sessionId' => $session->session_id,
            'workdir' => $session->workspace?->path,
        ]);

        if ($response['success'] && $response['session_id'] !== null) {
            $session->forceFill([
                'session_id' => $response['session_id'],
                'last_used_at' => now(),
            ])->saveQuietly();
        }

        $session->promptLogs()->create([
            'prompt' => $prompt,
            'response' => $response['result'],
            'success' => $response['success'],
            'error' => $response['error'],
        ]);

        return response()->json([
            'success' => $response['success'],
            'result' => $response['result'],
            'error' => $response['error'],
            'agent_session' => [
                'id' => $session->id,
                'agent_id' => $session->agent_id,
                'workspace_id' => $session->workspace_id,
                'session_id' => $session->session_id,
                'last_used_at' => $session->last_used_at,
                'created_at' => $session->created_at,
                'updated_at' => $session->updated_at,
            ],
            'raw' => $response['raw'],
        ], $response['success'] ? 200 : 502);
    }
}
