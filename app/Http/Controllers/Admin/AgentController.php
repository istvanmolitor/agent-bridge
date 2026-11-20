<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AgentRequest;
use App\Models\Agent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
                ->get(['id', 'name', 'description', 'avatar_path', 'system_prompt', 'prompt_mode', 'model', 'created_at']),
        ]);
    }

    /**
     * Create a new agent.
     */
    public function store(AgentRequest $request): RedirectResponse
    {
        $agent = $request->user()->agents()->create($request->validated());

        if ($request->hasFile('avatar')) {
            $agent->update(['avatar_path' => $this->storeAvatar($request)]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent created.')]);

        return to_route('admin.agents.index');
    }

    /**
     * Update an existing agent.
     */
    public function update(AgentRequest $request, Agent $agent): RedirectResponse
    {
        Gate::authorize('update', $agent);

        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $this->deleteAvatar($agent);
            $data['avatar_path'] = $this->storeAvatar($request);
        }

        $agent->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent updated.')]);

        return to_route('admin.agents.index');
    }

    /**
     * Delete an agent.
     */
    public function destroy(Agent $agent): RedirectResponse
    {
        Gate::authorize('delete', $agent);

        $this->deleteAvatar($agent);

        $agent->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent deleted.')]);

        return to_route('admin.agents.index');
    }

    /**
     * Store the uploaded avatar on the public disk and return its path.
     */
    private function storeAvatar(AgentRequest $request): string
    {
        $path = $request->file('avatar')->store('agent-avatars', 'public');

        abort_if($path === false, 500, 'Az arckép feltöltése nem sikerült.');

        return $path;
    }

    /**
     * Remove the agent's current avatar file, if any.
     */
    private function deleteAvatar(Agent $agent): void
    {
        if ($agent->avatar_path !== null) {
            Storage::disk('public')->delete($agent->avatar_path);
        }
    }
}
