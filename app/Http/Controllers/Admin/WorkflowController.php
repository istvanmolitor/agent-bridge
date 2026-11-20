<?php

namespace App\Http\Controllers\Admin;

use App\Events\WorkflowNodeStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkflowRequest;
use App\Models\Agent;
use App\Models\Prompt;
use App\Models\Workflow;
use App\Models\Workspace;
use App\Workflows\NodeRegistry;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowController extends Controller
{
    /**
     * Show the user's workflows.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('admin/Workflows', [
            'workflows' => $request->user()->workflows()
                ->withCount(['nodes', 'connections'])
                ->latest()
                ->get(['id', 'name', 'description', 'created_at']),
        ]);
    }

    /**
     * Create a new workflow.
     */
    public function store(WorkflowRequest $request): RedirectResponse
    {
        $request->user()->workflows()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workflow created.')]);

        return to_route('admin.workflows.index');
    }

    /**
     * Update an existing workflow.
     */
    public function update(WorkflowRequest $request, Workflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $workflow);

        $workflow->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workflow updated.')]);

        return back();
    }

    /**
     * Delete a workflow.
     */
    public function destroy(Workflow $workflow): RedirectResponse
    {
        Gate::authorize('delete', $workflow);

        $workflow->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workflow deleted.')]);

        return to_route('admin.workflows.index');
    }

    /**
     * Show a single workflow with its node graph editor.
     */
    public function show(Workflow $workflow): Response
    {
        Gate::authorize('view', $workflow);

        return Inertia::render('admin/WorkflowShow', [
            'workflow' => $workflow->only(['id', 'name', 'description']),
            'nodeTypes' => NodeRegistry::options(),
            // Prompts the owner can pick from on a prompt node.
            'prompts' => Prompt::where('user_id', $workflow->user_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            // Agents the owner can run on an agent node.
            'agents' => Agent::where('user_id', $workflow->user_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            // Workspaces the owner can run an agent node in.
            'workspaces' => Workspace::where('user_id', $workflow->user_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            // Live prop: Reverb broadcasts WorkflowNodeStatusChanged on the
            // workflow's private channel, and Inertia re-fetches the nodes so the
            // canvas reflects run status without polling.
            'nodes' => Inertia::live(
                fn () => $workflow->nodes()
                    ->orderBy('id')
                    ->get(['id', 'type', 'name', 'description', 'status', 'error', 'settings', 'position_x', 'position_y']),
                on: WorkflowNodeStatusChanged::class,
                channel: new PrivateChannel('workflows.'.$workflow->id),
            ),
            'connections' => $workflow->connections()
                ->get(['id', 'source_node_id', 'target_node_id']),
        ]);
    }
}
