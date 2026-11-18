<?php

use App\Models\ApiKey;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('api keys page lists only the current user\'s keys', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $ownKey = ApiKey::factory()->for($admin)->create(['name' => 'Mine']);
    ApiKey::factory()->create(['name' => 'Someone else\'s']);

    $this->actingAs($admin)
        ->get(route('admin.api-keys.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/ApiKeys')
            ->has('apiKeys', 1)
            ->where('apiKeys.0.id', $ownKey->id)
            ->where('apiKeys.0.name', 'Mine')
            ->missing('apiKeys.0.token'),
        );
});

test('a new api key can be created and its plaintext token is flashed once', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)
        ->from(route('admin.api-keys.index'))
        ->post(route('admin.api-keys.store'), ['name' => 'CI server']);

    $response->assertRedirect(route('admin.api-keys.index'));

    $apiKey = $admin->apiKeys()->sole();
    expect($apiKey->name)->toBe('CI server');

    $page = $this->get(route('admin.api-keys.index'))->viewData('page');

    expect(ApiKey::hashToken($page['flash']['generatedApiKey']))->toBe($apiKey->token);
});

test('creating an api key requires a name', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.api-keys.index'))
        ->post(route('admin.api-keys.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('an admin can delete their own api key', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $apiKey = ApiKey::factory()->for($admin)->create();

    $this->actingAs($admin)
        ->delete(route('admin.api-keys.destroy', $apiKey))
        ->assertRedirect(route('admin.api-keys.index'));

    expect(ApiKey::find($apiKey->id))->toBeNull();
});

test('an admin cannot delete another admin\'s api key', function () {
    $owner = User::factory()->create(['is_admin' => true]);
    $apiKey = ApiKey::factory()->for($owner)->create();
    $otherAdmin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($otherAdmin)
        ->delete(route('admin.api-keys.destroy', $apiKey))
        ->assertForbidden();

    expect(ApiKey::find($apiKey->id))->not->toBeNull();
});

test('a non-admin user cannot access the api keys pages', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $apiKey = ApiKey::factory()->for($user)->create();

    $this->actingAs($user)->get(route('admin.api-keys.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.api-keys.store'), ['name' => 'x'])->assertForbidden();
    $this->actingAs($user)->delete(route('admin.api-keys.destroy', $apiKey))->assertForbidden();
});

test('a guest is redirected to login for the api keys pages', function () {
    $this->get(route('admin.api-keys.index'))->assertRedirect(route('login'));
});
