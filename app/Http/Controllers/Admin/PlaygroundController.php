<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlaygroundRunRequest;
use App\Models\ApiKey;
use App\Services\ClaudeCli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlaygroundController extends Controller
{
    /**
     * Show the prompt playground page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('admin/Playground', [
            'apiKeys' => $request->user()->apiKeys()
                ->with('agent:id,name')
                ->latest()
                ->get(['id', 'agent_id', 'name', 'claude_session_id']),
        ]);
    }

    /**
     * Run a prompt through the selected API key, continuing its own session.
     */
    public function run(PlaygroundRunRequest $request, ClaudeCli $claudeCli): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->user()->apiKeys()->with('agent')->findOrFail($request->validated('api_key_id'));
        $agent = $apiKey->agent;

        $response = $claudeCli->run($request->validated('prompt'), [
            'systemPrompt' => $agent->system_prompt,
            'promptMode' => $agent->prompt_mode,
            'model' => $agent->model,
            'sessionId' => $apiKey->claude_session_id,
        ]);

        if ($response['success'] && $response['session_id'] !== null) {
            $apiKey->forceFill(['claude_session_id' => $response['session_id']])->save();
        }

        return response()->json($response);
    }

    /**
     * Forget the stored session for an API key, starting the next chat fresh.
     */
    public function reset(Request $request): RedirectResponse
    {
        $apiKey = $request->user()->apiKeys()->findOrFail($request->integer('api_key_id'));

        $apiKey->forceFill(['claude_session_id' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Conversation reset.')]);

        return to_route('admin.playground.edit');
    }
}
