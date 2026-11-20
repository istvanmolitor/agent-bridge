<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkflowConnectionRequest;
use App\Models\Workflow;
use App\Models\WorkflowConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WorkflowConnectionController extends Controller
{
    /**
     * Create a connection between two nodes of the workflow.
     */
    public function store(WorkflowConnectionRequest $request, Workflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $workflow);

        $workflow->connections()->firstOrCreate($request->validated());

        return back();
    }

    /**
     * Delete a connection.
     */
    public function destroy(Workflow $workflow, WorkflowConnection $connection): RedirectResponse
    {
        Gate::authorize('update', $workflow);
        abort_unless($connection->workflow_id === $workflow->id, 404);

        $connection->delete();

        return back();
    }
}
