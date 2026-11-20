<?php

use App\Models\Prompt;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the prompts page lists only the current user\'s prompts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $own = Prompt::factory()->for($admin)->create(['name' => 'mine']);
    Prompt::factory()->create(['name' => 'theirs']);

    $this->actingAs($admin)
        ->get(route('admin.prompts.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Prompts')
            ->has('prompts', 1)
            ->where('prompts.0.id', $own->id)
            ->where('prompts.0.name', 'mine'),
        );
});

test('an admin can create a prompt', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.prompts.index'))
        ->post(route('admin.prompts.store'), [
            'name' => 'udvozlo-szoveg',
            'content' => 'Üdvözöllek!',
        ])
        ->assertRedirect(route('admin.prompts.index'));

    $prompt = $admin->prompts()->sole();
    expect($prompt->name)->toBe('udvozlo-szoveg')
        ->and($prompt->content)->toBe('Üdvözöllek!');
});

test('creating a prompt requires a name and content', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.prompts.index'))
        ->post(route('admin.prompts.store'), [
            'name' => '',
            'content' => '',
        ])
        ->assertSessionHasErrors(['name', 'content']);
});

test('prompt names are unique per user but not across users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Prompt::factory()->for($admin)->create(['name' => 'greeting']);

    // Same name for the same user is rejected.
    $this->actingAs($admin)
        ->from(route('admin.prompts.index'))
        ->post(route('admin.prompts.store'), [
            'name' => 'greeting',
            'content' => 'again',
        ])
        ->assertSessionHasErrors('name');

    // The same name is fine for a different user.
    Prompt::factory()->create(['name' => 'greeting']);
    expect(Prompt::where('name', 'greeting')->count())->toBe(2);
});

test('an admin can update their own prompt', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $prompt = Prompt::factory()->for($admin)->create(['name' => 'old']);

    $this->actingAs($admin)
        ->from(route('admin.prompts.index'))
        ->put(route('admin.prompts.update', $prompt), [
            'name' => 'new',
            'content' => 'Updated.',
        ])
        ->assertRedirect(route('admin.prompts.index'));

    expect($prompt->refresh()->name)->toBe('new')
        ->and($prompt->content)->toBe('Updated.');
});

test('an admin cannot update another user\'s prompt', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $othersPrompt = Prompt::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.prompts.update', $othersPrompt), [
            'name' => 'hijacked',
            'content' => 'x',
        ])
        ->assertForbidden();
});

test('an admin can delete their own prompt', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $prompt = Prompt::factory()->for($admin)->create();

    $this->actingAs($admin)
        ->delete(route('admin.prompts.destroy', $prompt))
        ->assertRedirect(route('admin.prompts.index'));

    expect(Prompt::find($prompt->id))->toBeNull();
});

test('a non-admin user cannot access the prompts pages', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $prompt = Prompt::factory()->for($user)->create();

    $this->actingAs($user)->get(route('admin.prompts.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.prompts.store'))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.prompts.destroy', $prompt))->assertForbidden();
});
