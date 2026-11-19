<?php

use App\Models\ApiKey;
use App\Models\PromptLog;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('logs page lists only the current user\'s logs', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $ownKey = ApiKey::factory()->for($admin)->create();
    PromptLog::factory()->for($admin)->for($ownKey, 'apiKey')->create(['prompt' => 'mine']);
    PromptLog::factory()->create(['prompt' => 'someone else']);

    $this->actingAs($admin)
        ->get(route('admin.logs.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Logs')
            ->has('logs.data', 1)
            ->where('logs.data.0.prompt', 'mine'),
        );
});

test('logs can be searched by prompt and response', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $key = ApiKey::factory()->for($admin)->create();
    PromptLog::factory()->for($admin)->for($key, 'apiKey')->create(['prompt' => 'about laravel', 'response' => 'a framework']);
    PromptLog::factory()->for($admin)->for($key, 'apiKey')->create(['prompt' => 'about php', 'response' => 'a language']);

    $this->actingAs($admin)
        ->get(route('admin.logs.index', ['search' => 'laravel']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.prompt', 'about laravel'),
        );

    $this->actingAs($admin)
        ->get(route('admin.logs.index', ['search' => 'language']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.prompt', 'about php'),
        );
});

test('logs can be filtered by api key', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $keyA = ApiKey::factory()->for($admin)->create();
    $keyB = ApiKey::factory()->for($admin)->create();
    PromptLog::factory()->for($admin)->for($keyA, 'apiKey')->create(['prompt' => 'from A']);
    PromptLog::factory()->for($admin)->for($keyB, 'apiKey')->create(['prompt' => 'from B']);

    $this->actingAs($admin)
        ->get(route('admin.logs.index', ['api_key_id' => $keyA->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.prompt', 'from A'),
        );
});

test('logs are paginated', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $key = ApiKey::factory()->for($admin)->create();
    PromptLog::factory()->for($admin)->for($key, 'apiKey')->count(25)->create();

    $this->actingAs($admin)
        ->get(route('admin.logs.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 20)
            ->where('logs.total', 25)
            ->where('logs.last_page', 2),
        );
});

test('non-admins cannot view logs', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.logs.index'))
        ->assertForbidden();
});
