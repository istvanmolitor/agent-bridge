<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkflowRequest;
use App\Models\Workflow;
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
            'nodes' => $workflow->nodes()
                ->orderBy('id')
                ->get(['id', 'name', 'description', 'position_x', 'position_y']),
            'connections' => $workflow->connections()
                ->get(['id', 'source_node_id', 'target_node_id']),
        ]);
    }
}
