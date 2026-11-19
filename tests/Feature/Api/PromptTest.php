<?php

use App\Models\Agent;
use App\Models\ApiKey;
use App\Models\PromptLog;
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

test('a successful prompt is logged against the api key', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => false,
            'result' => 'pong',
            'session_id' => 'sess-log',
        ])),
    ]);

    $user = User::factory()->create();
    $plainTextToken = ApiKey::generatePlainTextToken();
    $apiKey = ApiKey::factory()->for($user)->create(['token' => ApiKey::hashToken($plainTextToken)]);

    $this->postJson('/api/prompt', ['prompt' => 'ping'], [
        'Authorization' => "Bearer {$plainTextToken}",
    ])->assertOk();

    $log = PromptLog::query()->sole();

    expect($log->api_key_id)->toBe($apiKey->id)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->prompt)->toBe('ping')
        ->and($log->response)->toBe('pong')
        ->and($log->success)->toBeTrue()
        ->and($log->error)->toBeNull()
        ->and($log->session_id)->toBe('sess-log');
});

test('a failed prompt is logged with its error', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => true,
            'result' => 'something went wrong',
        ])),
    ]);

    $user = User::factory()->create();
    $plainTextToken = ApiKey::generatePlainTextToken();
    ApiKey::factory()->for($user)->create(['token' => ApiKey::hashToken($plainTextToken)]);

    $this->postJson('/api/prompt', ['prompt' => 'ping'], [
        'Authorization' => "Bearer {$plainTextToken}",
    ])->assertStatus(502);

    $log = PromptLog::query()->sole();

    expect($log->success)->toBeFalse()
        ->and($log->error)->toBe('something went wrong');
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

test('the first prompt applies the agent persona and stores the returned session id', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => false,
            'result' => 'pong',
            'session_id' => 'sess-new',
        ])),
    ]);

    $user = User::factory()->create();
    $agent = Agent::factory()->for($user)->create([
        'system_prompt' => 'You are a poet.',
        'prompt_mode' => 'override',
        'model' => 'claude-sonnet-5',
    ]);
    $plainTextToken = ApiKey::generatePlainTextToken();
    $apiKey = ApiKey::factory()->for($user)->for($agent)->create([
        'token' => ApiKey::hashToken($plainTextToken),
        'claude_session_id' => null,
    ]);

    $this->postJson('/api/prompt', ['prompt' => 'ping'], [
        'Authorization' => "Bearer {$plainTextToken}",
    ])->assertOk();

    Process::assertRan(fn ($process) => in_array('--system-prompt', $process->command, true)
        && in_array('You are a poet.', $process->command, true)
        && in_array('--model', $process->command, true)
        && in_array('claude-sonnet-5', $process->command, true)
        && ! in_array('--resume', $process->command, true));

    expect($apiKey->refresh()->claude_session_id)->toBe('sess-new');
});

test('a subsequent prompt resumes the stored session instead of re-applying the persona', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => false,
            'result' => 'pong',
            'session_id' => 'sess-1',
        ])),
    ]);

    $user = User::factory()->create();
    $agent = Agent::factory()->for($user)->create(['system_prompt' => 'You are a poet.']);
    $plainTextToken = ApiKey::generatePlainTextToken();
    ApiKey::factory()->for($user)->for($agent)->create([
        'token' => ApiKey::hashToken($plainTextToken),
        'claude_session_id' => 'sess-1',
    ]);

    $this->postJson('/api/prompt', ['prompt' => 'ping'], [
        'Authorization' => "Bearer {$plainTextToken}",
    ])->assertOk();

    Process::assertRan(fn ($process) => in_array('--resume', $process->command, true)
        && in_array('sess-1', $process->command, true)
        && ! in_array('--system-prompt', $process->command, true)
        && ! in_array('--append-system-prompt', $process->command, true));
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
