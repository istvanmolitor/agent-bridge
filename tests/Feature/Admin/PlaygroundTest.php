<?php

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

test('an admin can run a prompt and get the cli result as json', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => false,
            'result' => 'pong',
        ])),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->postJson(route('admin.playground.run'), ['prompt' => 'ping'])
        ->assertOk()
        ->assertJson(['success' => true, 'result' => 'pong']);
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
