<?php

use App\Models\Agent;
use App\Models\Prompt;
use App\Models\Workspace;
use App\Services\AgentService;
use App\Services\ClaudeCli;

test('it runs the prompt text through the claude cli with the agent and workspace settings', function () {
    $agent = Agent::factory()->create([
        'system_prompt' => 'You are helpful.',
        'prompt_mode' => 'append',
        'model' => 'haiku',
    ]);
    $workspace = Workspace::factory()->create(['path' => '/tmp/ws']);

    $cli = $this->mock(ClaudeCli::class);
    $cli->shouldReceive('run')
        ->once()
        ->with('Summarise this.', [
            'systemPrompt' => 'You are helpful.',
            'promptMode' => 'append',
            'model' => 'haiku',
            'workdir' => '/tmp/ws',
        ])
        ->andReturn([
            'success' => true,
            'result' => 'Done.',
            'error' => null,
            'session_id' => null,
            'raw' => null,
        ]);

    $response = app(AgentService::class)->prompt('Summarise this.', $agent, $workspace);

    expect($response['result'])->toBe('Done.');
});

test('it accepts a prompt model and runs its content', function () {
    $agent = Agent::factory()->create();
    $workspace = Workspace::factory()->create();
    $prompt = Prompt::factory()->create(['content' => 'Write a haiku.']);

    $this->mock(ClaudeCli::class)
        ->shouldReceive('run')
        ->once()
        ->with('Write a haiku.', Mockery::type('array'))
        ->andReturn([
            'success' => true,
            'result' => 'A haiku.',
            'error' => null,
            'session_id' => null,
            'raw' => null,
        ]);

    $response = app(AgentService::class)->prompt($prompt, $agent, $workspace);

    expect($response['result'])->toBe('A haiku.');
});
