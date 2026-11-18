<?php

use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\PlaygroundController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/api-keys');

    Route::get('api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    Route::get('playground', [PlaygroundController::class, 'edit'])->name('playground.edit');
    Route::post('playground', [PlaygroundController::class, 'run'])->name('playground.run');
});
