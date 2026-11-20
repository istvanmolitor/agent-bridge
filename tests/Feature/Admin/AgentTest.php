<?php

use App\Models\Agent;
use App\Models\AgentSession;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('the agents page lists only the current user\'s agents', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $own = Agent::factory()->for($admin)->create(['name' => 'Mine']);
    Agent::factory()->create(['name' => 'Someone else\'s']);

    $this->actingAs($admin)
        ->get(route('admin.agents.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Agents')
            ->has('agents', 1)
            ->where('agents.0.id', $own->id)
            ->where('agents.0.name', 'Mine'),
        );
});

test('an admin can create an agent', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.agents.index'))
        ->post(route('admin.agents.store'), [
            'name' => 'Költő',
            'description' => 'Versben válaszol',
            'system_prompt' => 'Te egy költő vagy.',
            'prompt_mode' => 'override',
            'model' => null,
        ])
        ->assertRedirect(route('admin.agents.index'));

    $agent = $admin->agents()->sole();
    expect($agent->name)->toBe('Költő')
        ->and($agent->prompt_mode)->toBe('override')
        ->and($agent->system_prompt)->toBe('Te egy költő vagy.');
});

test('an admin can upload an avatar when creating an agent', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.agents.index'))
        ->post(route('admin.agents.store'), [
            'name' => 'Arcos',
            'system_prompt' => 'Van arcképe.',
            'prompt_mode' => 'append',
            'avatar' => UploadedFile::fake()->image('face.png'),
        ])
        ->assertRedirect(route('admin.agents.index'));

    $agent = $admin->agents()->sole();
    expect($agent->avatar_path)->not->toBeNull()
        ->and($agent->avatar_url)->toContain($agent->avatar_path);
    Storage::disk('public')->assertExists($agent->avatar_path);
});

test('uploading a new avatar replaces and removes the old file', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['is_admin' => true]);
    $agent = Agent::factory()->for($admin)->create();

    $this->actingAs($admin)->put(route('admin.agents.update', $agent), [
        'name' => $agent->name,
        'system_prompt' => $agent->system_prompt,
        'prompt_mode' => $agent->prompt_mode,
        'avatar' => UploadedFile::fake()->image('first.png'),
    ])->assertRedirect(route('admin.agents.index'));

    $first = $agent->refresh()->avatar_path;

    $this->actingAs($admin)->put(route('admin.agents.update', $agent), [
        'name' => $agent->name,
        'system_prompt' => $agent->system_prompt,
        'prompt_mode' => $agent->prompt_mode,
        'avatar' => UploadedFile::fake()->image('second.png'),
    ])->assertRedirect(route('admin.agents.index'));

    $second = $agent->refresh()->avatar_path;

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

test('deleting an agent removes its avatar file', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['is_admin' => true]);
    $agent = Agent::factory()->for($admin)->create();

    $this->actingAs($admin)->put(route('admin.agents.update', $agent), [
        'name' => $agent->name,
        'system_prompt' => $agent->system_prompt,
        'prompt_mode' => $agent->prompt_mode,
        'avatar' => UploadedFile::fake()->image('face.png'),
    ]);

    $path = $agent->refresh()->avatar_path;
    Storage::disk('public')->assertExists($path);

    $this->actingAs($admin)->delete(route('admin.agents.destroy', $agent));

    Storage::disk('public')->assertMissing($path);
});

test('the avatar must be a valid image within the size limit', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.agents.index'))
        ->post(route('admin.agents.store'), [
            'name' => 'Rossz',
            'system_prompt' => 'x',
            'prompt_mode' => 'append',
            'avatar' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');
});

test('creating an agent requires a name, system prompt and valid prompt mode', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.agents.index'))
        ->post(route('admin.agents.store'), [
            'name' => '',
            'system_prompt' => '',
            'prompt_mode' => 'nonsense',
        ])
        ->assertSessionHasErrors(['name', 'system_prompt', 'prompt_mode']);
});

test('an admin can update their own agent', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $agent = Agent::factory()->for($admin)->create(['name' => 'Old']);

    $this->actingAs($admin)
        ->from(route('admin.agents.index'))
        ->put(route('admin.agents.update', $agent), [
            'name' => 'New',
            'system_prompt' => 'Updated persona.',
            'prompt_mode' => 'append',
        ])
        ->assertRedirect(route('admin.agents.index'));

    expect($agent->refresh()->name)->toBe('New')
        ->and($agent->system_prompt)->toBe('Updated persona.');
});

test('an admin cannot update another user\'s agent', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $othersAgent = Agent::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.agents.update', $othersAgent), [
            'name' => 'Hijacked',
            'system_prompt' => 'x',
            'prompt_mode' => 'append',
        ])
        ->assertForbidden();
});

test('deleting an agent cascades to its sessions and api keys', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $agent = Agent::factory()->for($admin)->create();
    $session = AgentSession::factory()->for($admin)->for($agent)->create();
    $apiKey = ApiKey::factory()->for($admin)->for($session, 'agentSession')->create();

    $this->actingAs($admin)
        ->delete(route('admin.agents.destroy', $agent))
        ->assertRedirect(route('admin.agents.index'));

    expect(Agent::find($agent->id))->toBeNull()
        ->and(AgentSession::find($session->id))->toBeNull()
        ->and(ApiKey::find($apiKey->id))->toBeNull();
});

test('a non-admin user cannot access the agents pages', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $agent = Agent::factory()->for($user)->create();

    $this->actingAs($user)->get(route('admin.agents.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.agents.store'))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.agents.destroy', $agent))->assertForbidden();
});
