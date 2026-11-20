<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\ApiKey;
use App\Models\PromptLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromptLogController extends Controller
{
    /**
     * Show a searchable, paginated list of prompt logs across all users.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer'],
            'api_key_id' => ['nullable', 'integer'],
            'agent_id' => ['nullable', 'integer'],
            'workspace_id' => ['nullable', 'integer'],
        ]);

        $search = $validated['search'] ?? null;
        $userId = $validated['user_id'] ?? null;
        $apiKeyId = $validated['api_key_id'] ?? null;
        $agentId = $validated['agent_id'] ?? null;
        $workspaceId = $validated['workspace_id'] ?? null;

        $logs = PromptLog::query()
            ->with([
                'agentSession.user:id,name',
                'agentSession.apiKey:id,agent_session_id,name',
            ])
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('prompt', 'like', "%{$search}%")
                        ->orWhere('response', 'like', "%{$search}%");
                });
            })
            ->when($userId, fn ($query) => $query->whereHas(
                'agentSession',
                fn ($q) => $q->where('user_id', $userId),
            ))
            ->when($apiKeyId, fn ($query) => $query->whereHas(
                'agentSession.apiKey',
                fn ($q) => $q->where('id', $apiKeyId),
            ))
            ->when($agentId, fn ($query) => $query->whereHas(
                'agentSession',
                fn ($q) => $q->where('agent_id', $agentId),
            ))
            ->when($workspaceId, fn ($query) => $query->whereHas(
                'agentSession',
                fn ($q) => $q->where('workspace_id', $workspaceId),
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PromptLog $log) => [
                'id' => $log->id,
                'prompt' => $log->prompt,
                'response' => $log->response,
                'success' => $log->success,
                'error' => $log->error,
                'session_id' => $log->agentSession?->session_id,
                'created_at' => $log->created_at,
                'user' => $log->agentSession?->user
                    ? ['id' => $log->agentSession->user->id, 'name' => $log->agentSession->user->name]
                    : null,
                'api_key' => $log->agentSession?->apiKey
                    ? ['id' => $log->agentSession->apiKey->id, 'name' => $log->agentSession->apiKey->name]
                    : null,
            ]);

        return Inertia::render('admin/Logs', [
            'logs' => $logs,
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'apiKeys' => ApiKey::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'agents' => Agent::query()
                ->orderBy('name')
                ->get(['id', 'name', 'avatar_path']),
            'workspaces' => Workspace::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'user_id' => $userId !== null ? (string) $userId : null,
                'api_key_id' => $apiKeyId !== null ? (string) $apiKeyId : null,
                'agent_id' => $agentId !== null ? (string) $agentId : null,
                'workspace_id' => $workspaceId !== null ? (string) $workspaceId : null,
            ],
        ]);
    }
}
