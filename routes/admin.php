<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\PromptController;
use App\Http\Controllers\Admin\PromptLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorkflowConnectionController;
use App\Http\Controllers\Admin\WorkflowController;
use App\Http\Controllers\Admin\WorkflowNodeController;
use App\Http\Controllers\Admin\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/agents');

    Route::get('agents', [AgentController::class, 'index'])->name('agents.index');
    Route::post('agents', [AgentController::class, 'store'])->name('agents.store');
    Route::put('agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
    Route::delete('agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');

    Route::get('prompts', [PromptController::class, 'index'])->name('prompts.index');
    Route::post('prompts', [PromptController::class, 'store'])->name('prompts.store');
    Route::put('prompts/{prompt}', [PromptController::class, 'update'])->name('prompts.update');
    Route::delete('prompts/{prompt}', [PromptController::class, 'destroy'])->name('prompts.destroy');

    Route::get('workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::get('workspaces/{workspace}', [WorkspaceController::class, 'show'])->name('workspaces.show');
    Route::put('workspaces/{workspace}', [WorkspaceController::class, 'update'])->name('workspaces.update');
    Route::delete('workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');
    Route::get('workspaces/{workspace}/tree', [WorkspaceController::class, 'tree'])->name('workspaces.tree');
    Route::get('workspaces/{workspace}/file', [WorkspaceController::class, 'file'])->name('workspaces.file');

    Route::get('workflows', [WorkflowController::class, 'index'])->name('workflows.index');
    Route::post('workflows', [WorkflowController::class, 'store'])->name('workflows.store');
    Route::get('workflows/{workflow}', [WorkflowController::class, 'show'])->name('workflows.show');
    Route::put('workflows/{workflow}', [WorkflowController::class, 'update'])->name('workflows.update');
    Route::delete('workflows/{workflow}', [WorkflowController::class, 'destroy'])->name('workflows.destroy');

    Route::post('workflows/{workflow}/nodes', [WorkflowNodeController::class, 'store'])->name('workflows.nodes.store');
    Route::put('workflows/{workflow}/nodes/{node}', [WorkflowNodeController::class, 'update'])->name('workflows.nodes.update');
    Route::patch('workflows/{workflow}/nodes/{node}/position', [WorkflowNodeController::class, 'position'])->name('workflows.nodes.position');
    Route::post('workflows/{workflow}/nodes/{node}/run', [WorkflowNodeController::class, 'run'])->name('workflows.nodes.run');
    Route::delete('workflows/{workflow}/nodes/{node}', [WorkflowNodeController::class, 'destroy'])->name('workflows.nodes.destroy');

    Route::post('workflows/{workflow}/connections', [WorkflowConnectionController::class, 'store'])->name('workflows.connections.store');
    Route::delete('workflows/{workflow}/connections/{connection}', [WorkflowConnectionController::class, 'destroy'])->name('workflows.connections.destroy');

    Route::get('api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('logs', [PromptLogController::class, 'index'])->name('logs.index');

    Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('chat', [ChatController::class, 'run'])->name('chat.run');
    Route::post('chat/sessions', [ChatController::class, 'store'])->name('chat.sessions.store');
});
