<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApiKeyStoreRequest;
use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    /**
     * Show the user's API keys.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('admin/ApiKeys', [
            'apiKeys' => $request->user()->apiKeys()
                ->with(['agentSession.agent:id,name,avatar_path', 'agentSession.workspace:id,name'])
                ->latest()
                ->get(['id', 'agent_session_id', 'name', 'token', 'last_used_at', 'created_at'])
                ->map(fn (ApiKey $apiKey) => [
                    'id' => $apiKey->id,
                    'name' => $apiKey->name,
                    'token' => $apiKey->token,
                    'last_used_at' => $apiKey->last_used_at,
                    'created_at' => $apiKey->created_at,
                    'agent' => $apiKey->agentSession?->agent,
                    'workspace' => $apiKey->agentSession?->workspace,
                ]),
            'agents' => $request->user()->agents()
                ->orderBy('name')
                ->get(['id', 'name', 'avatar_path']),
            'workspaces' => $request->user()->workspaces()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Generate a new API key for the user.
     */
    public function store(ApiKeyStoreRequest $request): RedirectResponse
    {
        $session = $request->user()->agentSessions()->create([
            'agent_id' => $request->validated('agent_id'),
            'workspace_id' => $request->validated('workspace_id'),
        ]);

        $request->user()->apiKeys()->create([
            'agent_session_id' => $session->id,
            'name' => $request->validated('name'),
            'token' => ApiKey::generateToken(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('API key created.')]);

        return to_route('admin.api-keys.index');
    }

    /**
     * Delete an API key.
     */
    public function destroy(ApiKey $apiKey): RedirectResponse
    {
        Gate::authorize('delete', $apiKey);

        $apiKey->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('API key deleted.')]);

        return to_route('admin.api-keys.index');
    }
}
