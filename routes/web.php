<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/admin/chat')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
