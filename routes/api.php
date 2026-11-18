<?php

use App\Http\Controllers\Api\PromptController;
use Illuminate\Support\Facades\Route;

Route::post('prompt', [PromptController::class, 'store'])
    ->middleware('auth.apikey')
    ->name('api.prompt');
