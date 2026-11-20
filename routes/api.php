<?php

use App\Http\Controllers\Api\PromptController;
use App\Http\Controllers\Api\PromptLibraryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.apikey')->group(function () {
    Route::post('prompt', [PromptController::class, 'store'])
        ->name('api.prompt');

    Route::get('prompts/{name}', [PromptLibraryController::class, 'show'])
        ->where('name', '.*')
        ->name('api.prompts.show');
});
