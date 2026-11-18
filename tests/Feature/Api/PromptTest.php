<?php

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Support\Facades\Process;

test('a valid api key can submit a prompt and receive the cli result', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => false,
            'result' => 'pong',
        ])),
    ]);

    $user = User::factory()->create();
    $plainTextToken = ApiKey::generatePlainTextToken();
    ApiKey::factory()->for($user)->create(['token' => ApiKey::hashToken($plainTextToken)]);

    $response = $this->postJson('/api/prompt', ['prompt' => 'ping'], [
        'Authorization' => "Bearer {$plainTextToken}",
    ]);

    $response->assertOk()->assertJson([
        'success' => true,
        'result' => 'pong',
    ]);
});

test('a missing api key is rejected', function () {
    $response = $this->postJson('/api/prompt', ['prompt' => 'ping']);

    $response->assertUnauthorized();
});

test('an invalid api key is rejected', function () {
    $response = $this->postJson('/api/prompt', ['prompt' => 'ping'], [
        'Authorization' => 'Bearer not-a-real-token',
    ]);

    $response->assertUnauthorized();
});

test('using an api key updates its last_used_at timestamp', function () {
    Process::fake([
        '*' => Process::result(output: json_encode(['type' => 'result', 'is_error' => false, 'result' => 'pong'])),
    ]);

    $user = User::factory()->create();
    $plainTextToken = ApiKey::generatePlainTextToken();
    $apiKey = ApiKey::factory()->for($user)->create([
        'token' => ApiKey::hashToken($plainTextToken),
        'last_used_at' => null,
    ]);

    $this->postJson('/api/prompt', ['prompt' => 'ping'], [
        'Authorization' => "Bearer {$plainTextToken}",
    ]);

    expect($apiKey->refresh()->last_used_at)->not->toBeNull();
});
