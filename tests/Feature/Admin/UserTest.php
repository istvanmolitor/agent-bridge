<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('the users page lists all users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Users')
            ->has('users', 3),
        );
});

test('an admin can create a user', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->post(route('admin.users.store'), [
            'name' => 'Kovács Anna',
            'email' => 'anna@example.com',
            'password' => 'password123',
            'is_admin' => 'on',
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'anna@example.com')->sole();
    expect($user->name)->toBe('Kovács Anna')
        ->and($user->is_admin)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password123', $user->password))->toBeTrue();
});

test('a created user is a non-admin by default', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Sima Péter',
            'email' => 'peter@example.com',
            'password' => 'password123',
        ])
        ->assertRedirect();

    expect(User::where('email', 'peter@example.com')->sole()->is_admin)->toBeFalse();
});

test('creating a user requires a name, unique email and password', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->post(route('admin.users.store'), [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => '',
        ])
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

test('an admin can update a user without changing the password', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Old',
        'email' => 'old@example.com',
        'password' => Hash::make('original'),
        'is_admin' => false,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->put(route('admin.users.update', $user), [
            'name' => 'New',
            'email' => 'new@example.com',
            'is_admin' => 'on',
        ])
        ->assertRedirect(route('admin.users.index'));

    $user->refresh();
    expect($user->name)->toBe('New')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->is_admin)->toBeTrue()
        ->and(Hash::check('original', $user->password))->toBeTrue();
});

test('an admin can change a user password on update', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['password' => Hash::make('original')]);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'brandnew123',
        ])
        ->assertRedirect();

    expect(Hash::check('brandnew123', $user->refresh()->password))->toBeTrue();
});

test('updating a user allows keeping the same email', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['email' => 'keep@example.com']);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), [
            'name' => 'Renamed',
            'email' => 'keep@example.com',
        ])
        ->assertRedirect();

    expect($user->refresh()->name)->toBe('Renamed');
});

test('an admin can delete another user', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users.index'));

    expect(User::find($user->id))->toBeNull();
});

test('an admin cannot delete their own account', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertForbidden();

    expect(User::find($admin->id))->not->toBeNull();
});

test('a non-admin user cannot access the users pages', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $other = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.users.store'))->assertForbidden();
    $this->actingAs($user)->put(route('admin.users.update', $other))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.users.destroy', $other))->assertForbidden();
});
