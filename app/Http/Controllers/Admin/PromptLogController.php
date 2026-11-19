<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $logs = $request->user()->promptLogs()
            ->with('apiKey:id,name')
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('prompt', 'like', "%{$search}%")
                        ->orWhere('response', 'like', "%{$search}%");
                });
            })
            ->when($apiKeyId, fn ($query) => $query->where('api_key_id', $apiKeyId))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($log) => [
                'id' => $log->id,
                'prompt' => $log->prompt,
                'response' => $log->response,
                'success' => $log->success,
                'error' => $log->error,
                'session_id' => $log->session_id,
                'created_at' => $log->created_at,
                'api_key' => $log->apiKey ? ['id' => $log->apiKey->id, 'name' => $log->apiKey->name] : null,
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
