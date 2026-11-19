<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromptLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromptLogController extends Controller
{
    /**
     * Show a searchable, paginated list of the user's prompt logs.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'api_key_id' => ['nullable', 'integer'],
        ]);

        $search = $validated['search'] ?? null;
        $apiKeyId = $validated['api_key_id'] ?? null;

        $logs = PromptLog::forUser($request->user()->id)
            ->with('agentSession.apiKey:id,agent_session_id,name')
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('prompt', 'like', "%{$search}%")
                        ->orWhere('response', 'like', "%{$search}%");
                });
            })
            ->when($apiKeyId, fn ($query) => $query->whereHas(
                'agentSession.apiKey',
                fn ($q) => $q->where('id', $apiKeyId),
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
                'api_key' => $log->agentSession?->apiKey
                    ? ['id' => $log->agentSession->apiKey->id, 'name' => $log->agentSession->apiKey->name]
                    : null,
            ]);

        return Inertia::render('admin/Logs', [
            'logs' => $logs,
            'apiKeys' => $request->user()->apiKeys()
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'api_key_id' => $apiKeyId !== null ? (string) $apiKeyId : null,
            ],
        ]);
    }
}
