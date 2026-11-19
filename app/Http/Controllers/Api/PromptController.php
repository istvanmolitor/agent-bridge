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
        $agent = $apiKey->agent;

        $response = $claudeCli->run($request->validated('prompt'), [
            'systemPrompt' => $agent->system_prompt,
            'promptMode' => $agent->prompt_mode,
            'model' => $agent->model,
            'sessionId' => $apiKey->claude_session_id,
        ]);

        if ($response['success'] && $response['session_id'] !== null) {
            $apiKey->forceFill(['claude_session_id' => $response['session_id']])->saveQuietly();
        }

        return response()->json($response, $response['success'] ? 200 : 502);
    }
}
