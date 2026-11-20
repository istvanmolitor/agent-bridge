<?php

use App\Models\ApiKey;
use App\Models\Prompt;
use App\Models\User;

test('a valid api key can fetch a stored prompt by name', function () {
    $user = User::factory()->create();
    $plainTextToken = ApiKey::generateToken();
    ApiKey::factory()->for($user)->create(['token' => $plainTextToken]);

    $prompt = Prompt::factory()->for($user)->create([
        'name' => 'udvozlo-szoveg',
        'content' => 'Üdvözöllek!',
    ]);

    $this->getJson('/api/prompts/udvozlo-szoveg', [
        'Authorization' => "Bearer {$plainTextToken}",
    ])->assertOk()->assertJson([
        'id' => $prompt->id,
        'name' => 'udvozlo-szoveg',
        'content' => 'Üdvözöllek!',
    ]);
});

test('a prompt from another user is not reachable', function () {
    $user = User::factory()->create();
    $plainTextToken = ApiKey::generateToken();
    ApiKey::factory()->for($user)->create(['token' => $plainTextToken]);

    Prompt::factory()->create(['name' => 'someone-elses']);

    $this->getJson('/api/prompts/someone-elses', [
        'Authorization' => "Bearer {$plainTextToken}",
    ])->assertNotFound();
});

test('fetching a prompt requires a valid api key', function () {
    $user = User::factory()->create();
    Prompt::factory()->for($user)->create(['name' => 'greeting']);

    $this->getJson('/api/prompts/greeting')->assertUnauthorized();
});
