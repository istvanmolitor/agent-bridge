<?php

use App\Models\Agent;
use App\Models\AgentSession;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Process;
use Inertia\Testing\AssertableInertia as Assert;

test('the chat page is displayed to an admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.chat.index'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/Chat'));
});

test('sessions are listed with the most recently used one first', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    // Used recently, but created a while ago.
    $recentlyUsed = AgentSession::factory()->for($admin)->create([
        'created_at' => now()->subDays(2),
        'last_used_at' => now()->subMinutes(5),
    ]);

    // Brand new session that has never been used yet.
    $brandNew = AgentSession::factory()->fresh()->for($admin)->create([
        'created_at' => now(),
    ]);

    // Used long ago.
    $stale = AgentSession::factory()->for($admin)->create([
        'created_at' => now()->subDays(10),
        'last_used_at' => now()->subDays(3),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.chat.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Chat')
            ->where('sessions.0.id', $brandNew->id)
            ->where('sessions.1.id', $recentlyUsed->id)
            ->where('sessions.2.id', $stale->id));
});

test('guests are redirected to login for the chat page', function () {
    $this->get(route('admin.chat.index'))->assertRedirect(route('login'));
});

test('a non-admin user cannot access the chat page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('admin.chat.index'))->assertForbidden();
});

test('an admin can create a session and is taken to its chat', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $agent = Agent::factory()->for($admin)->create();
    $workspace = Workspace::factory()->for($admin)->create();

    $this->actingAs($admin)
        ->post(route('admin.chat.sessions.store'), [
            'agent_id' => $agent->id,
            'workspace_id' => $workspace->id,
        ])
        ->assertRedirect();

    $session = $admin->agentSessions()->sole();
    expect($session->agent_id)->toBe($agent->id)
        ->and($session->workspace_id)->toBe($workspace->id)
        ->and($session->session_id)->toBeNull();
});

test('creating a session requires an agent and a workspace', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.chat.index'))
        ->post(route('admin.chat.sessions.store'), [])
        ->assertSessionHasErrors(['agent_id', 'workspace_id']);
});

test('an admin can run a prompt through a session and get the cli result as json', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => false,
            'result' => 'pong',
            'session_id' => 'sess-1',
        ])),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $session = AgentSession::factory()->fresh()->for($admin)->create();

    $this->actingAs($admin)
        ->postJson(route('admin.chat.run'), ['prompt' => 'ping', 'agent_session_id' => $session->id])
        ->assertOk()
        ->assertJson(['success' => true, 'result' => 'pong']);

    expect($session->refresh()->session_id)->toBe('sess-1');
});

test('running a prompt continues the session', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => false,
            'result' => 'pong',
            'session_id' => 'sess-2',
        ])),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $session = AgentSession::factory()->for($admin)->create(['session_id' => 'sess-1']);

    $this->actingAs($admin)
        ->postJson(route('admin.chat.run'), ['prompt' => 'ping', 'agent_session_id' => $session->id])
        ->assertOk();

    Process::assertRan(fn ($process) => in_array('--resume', $process->command, true)
        && in_array('sess-1', $process->command, true));
});

test('an admin cannot run a prompt through another user\'s session', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $othersSession = AgentSession::factory()->create();

    $this->actingAs($admin)
        ->postJson(route('admin.chat.run'), ['prompt' => 'ping', 'agent_session_id' => $othersSession->id])
        ->assertStatus(422);
});

test('a non-admin user cannot run a prompt', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->postJson(route('admin.chat.run'), ['prompt' => 'ping'])
        ->assertForbidden();
});

test('guests cannot run a prompt', function () {
    $this->postJson(route('admin.chat.run'), ['prompt' => 'ping'])->assertUnauthorized();
});
