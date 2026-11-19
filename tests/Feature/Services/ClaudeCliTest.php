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
