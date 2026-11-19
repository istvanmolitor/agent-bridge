<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AgentRequest;
use App\Models\Agent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    /**
     * Show the user's agents.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('admin/Agents', [
            'agents' => $request->user()->agents()
                ->withCount('apiKeys')
                ->latest()
                ->get(['id', 'name', 'description', 'system_prompt', 'prompt_mode', 'model', 'created_at']),
        ]);
    }

    /**
     * Create a new agent.
     */
    public function store(AgentRequest $request): RedirectResponse
    {
        $request->user()->agents()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent created.')]);

        return to_route('admin.agents.index');
    }

    /**
     * Update an existing agent.
     */
    public function update(AgentRequest $request, Agent $agent): RedirectResponse
    {
        Gate::authorize('update', $agent);

        $agent->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent updated.')]);

        return to_route('admin.agents.index');
    }

    /**
     * Delete an agent.
     */
    public function destroy(Agent $agent): RedirectResponse
    {
        Gate::authorize('delete', $agent);

        $agent->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent deleted.')]);

        return to_route('admin.agents.index');
    }
}
