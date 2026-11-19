<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkspaceRequest;
use App\Models\Workspace;
use App\Services\WorkspaceFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    /**
     * Show the user's workspaces.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('admin/Workspaces', [
            'base' => Workspace::basePath(),
            'workspaces' => $request->user()->workspaces()
                ->withCount('apiKeys')
                ->latest()
                ->get(['id', 'name', 'path', 'created_at'])
                ->each->append('relative_path'),
        ]);
    }

    /**
     * Create a new workspace and ensure its physical directory exists.
     */
    public function store(WorkspaceRequest $request): RedirectResponse
    {
        $this->ensureWorkspaceDirectory($request->resolvedPath());

        $request->user()->workspaces()->create($request->workspaceAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace created.')]);

        return to_route('admin.workspaces.index');
    }

    /**
     * Update an existing workspace and ensure its physical directory exists.
     */
    public function update(WorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
        Gate::authorize('update', $workspace);

        $this->ensureWorkspaceDirectory($request->resolvedPath());

        $workspace->update($request->workspaceAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace updated.')]);

        return to_route('admin.workspaces.index');
    }

    /**
     * Create the workspace directory if needed, failing with a validation
     * error (instead of a 500) when it cannot be created or written to.
     *
     * @throws ValidationException
     */
    protected function ensureWorkspaceDirectory(string $path): void
    {
        if (! is_dir($path)) {
            // Silenced so a permission warning is not promoted to an ErrorException.
            @mkdir($path, 0775, true);
        }

        $real = realpath($path);
        $base = realpath(Workspace::basePath());

        $withinBase = $real !== false && $base !== false
            && ($real === $base || str_starts_with($real, $base.DIRECTORY_SEPARATOR));

        if ($real === false || ! is_dir($real) || ! is_writable($real) || ! $withinBase) {
            throw ValidationException::withMessages([
                'folder' => __('The folder could not be created or is not writable under :base. Check its name and permissions.', ['base' => Workspace::basePath()]),
            ]);
        }
    }

    /**
     * Delete a workspace record. The physical directory is left untouched.
     */
    public function destroy(Workspace $workspace): RedirectResponse
    {
        Gate::authorize('delete', $workspace);

        $workspace->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace deleted.')]);

        return to_route('admin.workspaces.index');
    }

    /**
     * Show a single workspace with its file tree.
     */
    public function show(Workspace $workspace): Response
    {
        Gate::authorize('view', $workspace);

        return Inertia::render('admin/WorkspaceShow', [
            'workspace' => $workspace->only(['id', 'name', 'path']),
        ]);
    }

    /**
     * List the immediate children of a directory within the workspace.
     */
    public function tree(Request $request, Workspace $workspace, WorkspaceFiles $files): JsonResponse
    {
        Gate::authorize('view', $workspace);

        return response()->json([
            'entries' => $files->children($workspace, (string) $request->query('path', '')),
        ]);
    }

    /**
     * Read a single file within the workspace.
     */
    public function file(Request $request, Workspace $workspace, WorkspaceFiles $files): JsonResponse
    {
        Gate::authorize('view', $workspace);

        $request->validate(['path' => ['required', 'string']]);

        return response()->json($files->read($workspace, (string) $request->query('path')));
    }
}
