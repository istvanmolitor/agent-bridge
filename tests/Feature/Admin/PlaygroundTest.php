<?php

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Support\Facades\Process;
use Inertia\Testing\AssertableInertia as Assert;

test('the playground page is displayed to an admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.playground.edit'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/Playground'));
});

test('guests are redirected to login for the playground page', function () {
    $this->get(route('admin.playground.edit'))->assertRedirect(route('login'));
});

test('a non-admin user cannot access the playground page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('admin.playground.edit'))->assertForbidden();
});

test('an admin can run a prompt through an api key and get the cli result as json', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => false,
            'result' => 'pong',
            'session_id' => 'sess-1',
        ])),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $apiKey = ApiKey::factory()->for($admin)->create();

    $this->actingAs($admin)
        ->postJson(route('admin.playground.run'), ['prompt' => 'ping', 'api_key_id' => $apiKey->id])
        ->assertOk()
        ->assertJson(['success' => true, 'result' => 'pong']);

    expect($apiKey->refresh()->claude_session_id)->toBe('sess-1');
});

test('running a prompt continues the api key\'s stored session', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => false,
            'result' => 'pong',
            'session_id' => 'sess-2',
        ])),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $apiKey = ApiKey::factory()->for($admin)->create(['claude_session_id' => 'sess-1']);

    $this->actingAs($admin)
        ->postJson(route('admin.playground.run'), ['prompt' => 'ping', 'api_key_id' => $apiKey->id])
        ->assertOk();

    Process::assertRan(fn ($process) => in_array('--resume', $process->command, true)
        && in_array('sess-1', $process->command, true));
});

test('resetting an api key session clears the stored session id', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $apiKey = ApiKey::factory()->for($admin)->create(['claude_session_id' => 'sess-1']);

    $this->actingAs($admin)
        ->from(route('admin.playground.edit'))
        ->post(route('admin.playground.reset'), ['api_key_id' => $apiKey->id])
        ->assertRedirect(route('admin.playground.edit'));

    expect($apiKey->refresh()->claude_session_id)->toBeNull();
});

test('an admin cannot run a prompt through another user\'s api key', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $othersKey = ApiKey::factory()->create();

    $this->actingAs($admin)
        ->postJson(route('admin.playground.run'), ['prompt' => 'ping', 'api_key_id' => $othersKey->id])
        ->assertStatus(422);
});

test('a non-admin user cannot run a prompt', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->postJson(route('admin.playground.run'), ['prompt' => 'ping'])
        ->assertForbidden();
});

test('guests cannot run a prompt', function () {
    $this->postJson(route('admin.playground.run'), ['prompt' => 'ping'])->assertUnauthorized();
});
