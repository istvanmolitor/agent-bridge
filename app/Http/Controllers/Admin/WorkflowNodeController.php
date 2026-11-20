<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkflowNodeRequest;
use App\Models\Workflow;
use App\Models\WorkflowNode;
use App\Workflows\WorkflowRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WorkflowNodeController extends Controller
{
    /**
     * Create a new node within the workflow.
     */
    public function store(WorkflowNodeRequest $request, Workflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $workflow);

        $workflow->nodes()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Node created.')]);

        return back();
    }

    /**
     * Update a node's name and description.
     */
    public function update(WorkflowNodeRequest $request, Workflow $workflow, WorkflowNode $node): RedirectResponse
    {
        Gate::authorize('update', $workflow);
        abort_unless($node->workflow_id === $workflow->id, 404);

        $node->update($request->safe()->only(['name', 'description', 'settings']));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Node updated.')]);

        return back();
    }

    /**
     * Persist a node's canvas position after it has been dragged.
     */
    public function position(Request $request, Workflow $workflow, WorkflowNode $node): RedirectResponse
    {
        Gate::authorize('update', $workflow);
        abort_unless($node->workflow_id === $workflow->id, 404);

        $validated = $request->validate([
            'position_x' => ['required', 'numeric'],
            'position_y' => ['required', 'numeric'],
        ]);

        $node->update($validated);

        return back();
    }

    /**
     * Start the workflow from this node by queueing it for execution.
     */
    public function run(Workflow $workflow, WorkflowNode $node, WorkflowRunner $runner): RedirectResponse
    {
        Gate::authorize('update', $workflow);
        abort_unless($node->workflow_id === $workflow->id, 404);

        if (! $runner->run($node)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $node->error]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workflow started.')]);

        return back();
    }

    /**
     * Delete a node. Its connections are removed via the database cascade.
     */
    public function destroy(Workflow $workflow, WorkflowNode $node): RedirectResponse
    {
        Gate::authorize('update', $workflow);
        abort_unless($node->workflow_id === $workflow->id, 404);

        $node->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Node deleted.')]);

        return back();
    }
}
