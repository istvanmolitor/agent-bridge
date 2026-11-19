<?php

use App\Services\ClaudeCli;
use Illuminate\Support\Facades\Process;

test('a successful cli response is parsed into a result', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => false,
            'result' => 'pong',
            'session_id' => 'abc123',
        ])),
    ]);

    $response = (new ClaudeCli)->run('ping');

    expect($response)->toBe([
        'success' => true,
        'result' => 'pong',
        'error' => null,
        'session_id' => 'abc123',
        'raw' => [
            'type' => 'result',
            'is_error' => false,
            'result' => 'pong',
            'session_id' => 'abc123',
        ],
    ]);
});

test('an is_error cli payload is surfaced as a failure', function () {
    Process::fake([
        '*' => Process::result(output: json_encode([
            'type' => 'result',
            'is_error' => true,
            'result' => 'something went wrong',
        ])),
    ]);

    $response = (new ClaudeCli)->run('ping');

    expect($response['success'])->toBeFalse()
        ->and($response['error'])->toBe('something went wrong');
});

test('a non-zero exit code is surfaced as a failure', function () {
    Process::fake([
        '*' => Process::result(output: '', errorOutput: 'command not found', exitCode: 127),
    ]);

    $response = (new ClaudeCli)->run('ping');

    expect($response['success'])->toBeFalse()
        ->and($response['error'])->toBe('command not found');
});

test('unparsable output is surfaced as a failure', function () {
    Process::fake([
        '*' => Process::result(output: 'not json'),
    ]);

    $response = (new ClaudeCli)->run('ping');

    expect($response['success'])->toBeFalse()
        ->and($response['raw'])->toBe('not json');
});

test('the cli runs in the provided workdir', function () {
    Process::fake(['*' => Process::result(output: json_encode(['is_error' => false, 'result' => 'ok']))]);
    $workdir = sys_get_temp_dir().'/agent-bridge-cli/'.bin2hex(random_bytes(6));

    (new ClaudeCli)->run('ping', ['workdir' => $workdir]);

    Process::assertRan(fn ($process) => $process->path === $workdir);
});

test('the cli falls back to the configured workdir when none is given', function () {
    Process::fake(['*' => Process::result(output: json_encode(['is_error' => false, 'result' => 'ok']))]);

    (new ClaudeCli)->run('ping');

    Process::assertRan(fn ($process) => $process->path === config('services.claude_cli.workdir'));
});

/**
 * Returns the value passed to a given flag in a faked process command array.
 */
function cliFlagValue(array $command, string $flag): ?string
{
    $index = array_search($flag, $command, true);

    return $index === false ? null : ($command[$index + 1] ?? null);
}

test('the configured permission mode is passed to the cli', function () {
    config()->set('services.claude_cli.permission_mode', 'acceptEdits');
    Process::fake(['*' => Process::result(output: json_encode(['is_error' => false, 'result' => 'ok']))]);

    (new ClaudeCli)->run('ping');

    Process::assertRan(fn ($process) => cliFlagValue($process->command, '--permission-mode') === 'acceptEdits');
});

test('the permission mode flag is omitted when not configured', function () {
    config()->set('services.claude_cli.permission_mode', null);
    Process::fake(['*' => Process::result(output: json_encode(['is_error' => false, 'result' => 'ok']))]);

    (new ClaudeCli)->run('ping');

    Process::assertRan(fn ($process) => ! in_array('--permission-mode', $process->command, true));
});

test('the permission mode is applied to resumed sessions', function () {
    config()->set('services.claude_cli.permission_mode', 'bypassPermissions');
    Process::fake(['*' => Process::result(output: json_encode(['is_error' => false, 'result' => 'ok']))]);

    (new ClaudeCli)->run('ping', ['sessionId' => 'abc123']);

    Process::assertRan(fn ($process) => cliFlagValue($process->command, '--permission-mode') === 'bypassPermissions'
        && cliFlagValue($process->command, '--resume') === 'abc123');
});
