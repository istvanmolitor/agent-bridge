<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\PlaygroundController;
use App\Http\Controllers\Admin\PromptLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/agents');

    Route::get('agents', [AgentController::class, 'index'])->name('agents.index');
    Route::post('agents', [AgentController::class, 'store'])->name('agents.store');
    Route::put('agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
    Route::delete('agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');

    Route::get('api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    Route::get('logs', [PromptLogController::class, 'index'])->name('logs.index');

    Route::get('playground', [PlaygroundController::class, 'edit'])->name('playground.edit');
    Route::post('playground', [PlaygroundController::class, 'run'])->name('playground.run');
    Route::post('playground/reset', [PlaygroundController::class, 'reset'])->name('playground.reset');
});
