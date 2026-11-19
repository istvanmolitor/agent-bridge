<?php

use App\Models\AgentSession;
use App\Models\ApiKey;
use App\Models\PromptLog;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('logs page lists only the current user\'s logs', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $ownSession = AgentSession::factory()->for($admin)->create();
    PromptLog::factory()->for($ownSession, 'agentSession')->create(['prompt' => 'mine']);
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
    $session = AgentSession::factory()->for($admin)->create();
    PromptLog::factory()->for($session, 'agentSession')->create(['prompt' => 'about laravel', 'response' => 'a framework']);
    PromptLog::factory()->for($session, 'agentSession')->create(['prompt' => 'about php', 'response' => 'a language']);

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
    $sessionA = AgentSession::factory()->for($admin)->create();
    $sessionB = AgentSession::factory()->for($admin)->create();
    $keyA = ApiKey::factory()->for($admin)->for($sessionA, 'agentSession')->create();
    ApiKey::factory()->for($admin)->for($sessionB, 'agentSession')->create();
    PromptLog::factory()->for($sessionA, 'agentSession')->create(['prompt' => 'from A']);
    PromptLog::factory()->for($sessionB, 'agentSession')->create(['prompt' => 'from B']);

    $this->actingAs($admin)
        ->get(route('admin.logs.index', ['api_key_id' => $keyA->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.prompt', 'from A'),
        );
});

test('logs are paginated', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $session = AgentSession::factory()->for($admin)->create();
    PromptLog::factory()->for($session, 'agentSession')->count(25)->create();

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
