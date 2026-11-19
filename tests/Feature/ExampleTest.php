<?php

use App\Models\User;

test('guests are redirected from the home page to login', function () {
    $this->get(route('home'))
        ->assertRedirect(route('login'));
});

test('authenticated users are redirected from the home page to the chat', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertRedirect('/admin/chat');
});
