<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect('/admin/chat')
        : redirect()->route('login');
})->name('home');

Route::get('/api/docs', fn () => view('api-docs'))->name('api.docs');

Route::get('/api/openapi.yaml', function () {
    return response()
        ->file(resource_path('api/openapi.yaml'), ['Content-Type' => 'application/yaml']);
})->name('api.docs.openapi');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/admin/chat')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
