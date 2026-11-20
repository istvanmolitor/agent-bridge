<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromptRequest;
use App\Models\Prompt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PromptController extends Controller
{
    /**
     * Show the user's prompts.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('admin/Prompts', [
            'prompts' => $request->user()->prompts()
                ->latest()
                ->get(['id', 'name', 'content', 'created_at']),
        ]);
    }

    /**
     * Create a new prompt.
     */
    public function store(PromptRequest $request): RedirectResponse
    {
        $request->user()->prompts()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prompt created.')]);

        return to_route('admin.prompts.index');
    }

    /**
     * Update an existing prompt.
     */
    public function update(PromptRequest $request, Prompt $prompt): RedirectResponse
    {
        Gate::authorize('update', $prompt);

        $prompt->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prompt updated.')]);

        return to_route('admin.prompts.index');
    }

    /**
     * Delete a prompt.
     */
    public function destroy(Prompt $prompt): RedirectResponse
    {
        Gate::authorize('delete', $prompt);

        $prompt->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prompt deleted.')]);

        return to_route('admin.prompts.index');
    }
}
