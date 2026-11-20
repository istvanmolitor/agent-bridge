<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AgentSessionStoreRequest;
use App\Http\Requests\Admin\ChatRunRequest;
use App\Models\AgentSession;
use App\Models\PromptLog;
use App\Services\ClaudeCli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    /**
     * Show the session list and the chat for the selected session.
     */
    public function index(Request $request): Response
    {
        $sessions = $request->user()->agentSessions()
            ->with(['agent:id,name,avatar_path', 'workspace:id,name'])
            ->orderByRaw('COALESCE(last_used_at, created_at) DESC')
            ->latest('id')
            ->get(['id', 'agent_id', 'workspace_id', 'session_id', 'last_used_at', 'created_at'])
            ->map(fn (AgentSession $session) => [
                'id' => $session->id,
                'agent' => $session->agent,
                'workspace' => $session->workspace,
                'has_session' => $session->session_id !== null,
                'last_used_at' => $session->last_used_at,
                'created_at' => $session->created_at,
            ]);

        $sessionId = $request->integer('agent_session_id') ?: $sessions->first()['id'] ?? null;

        $logs = $sessionId
            ? PromptLog::query()
                ->where('agent_session_id', $sessionId)
                ->latest()
                ->limit(50)
                ->get(['id', 'prompt', 'response', 'success', 'error', 'created_at'])
                ->reverse()
                ->values()
            : collect();

        return Inertia::render('admin/Chat', [
            'sessions' => $sessions,
            'agents' => $request->user()->agents()->orderBy('name')->get(['id', 'name', 'avatar_path']),
            'workspaces' => $request->user()->workspaces()->orderBy('name')->get(['id', 'name']),
            'logs' => $logs,
            'selectedSessionId' => $sessionId ? (string) $sessionId : null,
        ]);
    }

    /**
     * Create a new session and open its chat.
     */
    public function store(AgentSessionStoreRequest $request): RedirectResponse
    {
        $session = $request->user()->agentSessions()->create([
            'agent_id' => $request->validated('agent_id'),
            'workspace_id' => $request->validated('workspace_id'),
        ]);

        return to_route('admin.chat.index', ['agent_session_id' => $session->id]);
    }

    /**
     * Run a prompt through the selected session, continuing its conversation.
     */
    public function run(ChatRunRequest $request, ClaudeCli $claudeCli): JsonResponse
    {
        /** @var AgentSession $session */
        $session = $request->user()->agentSessions()
            ->with(['agent', 'workspace'])
            ->findOrFail($request->validated('agent_session_id'));

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
            ])->save();
        }

        $session->promptLogs()->create([
            'prompt' => $prompt,
            'response' => $response['result'],
            'success' => $response['success'],
            'error' => $response['error'],
        ]);

        return response()->json($response);
    }
}
