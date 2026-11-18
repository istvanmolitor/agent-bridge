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
                ->latest()
                ->get(['id', 'name', 'last_used_at', 'created_at']),
        ]);
    }

    /**
     * Generate a new API key for the user.
     */
    public function store(ApiKeyStoreRequest $request): RedirectResponse
    {
        $plainTextToken = ApiKey::generatePlainTextToken();

        $request->user()->apiKeys()->create([
            'name' => $request->validated('name'),
            'token' => ApiKey::hashToken($plainTextToken),
        ]);

        Inertia::flash('generatedApiKey', $plainTextToken);
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
