<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class ClaudeCli
{
    /**
     * Run a prompt through the locally installed `claude` CLI and return its parsed response.
     *
     * Supported options:
     *  - systemPrompt (?string): the agent persona to apply when starting a new session.
     *  - promptMode (string): 'append' (default) or 'override' — how the persona is applied.
     *  - model (?string): model alias/name to use when starting a new session.
     *  - sessionId (?string): resume an existing conversation instead of starting a new one.
     *  - workdir (?string): directory the CLI runs in; falls back to the configured default.
     *
     * @param  array{systemPrompt?: ?string, promptMode?: string, model?: ?string, sessionId?: ?string, workdir?: ?string}  $options
     * @return array{success: bool, result: ?string, error: ?string, session_id: ?string, raw: mixed}
     */
    public function run(string $prompt, array $options = []): array
    {
        $workdir = ($options['workdir'] ?? null) ?: config('services.claude_cli.workdir');

        File::ensureDirectoryExists($workdir);

        $process = Process::path($workdir)
            ->timeout((int) config('services.claude_cli.timeout'))
            ->run($this->buildCommand($prompt, $options));

        if (! $process->successful()) {
            $errorOutput = trim($process->errorOutput());

            return [
                'success' => false,
                'result' => null,
                'error' => $errorOutput !== '' ? $errorOutput : 'The claude command exited with an error.',
                'session_id' => null,
                'raw' => null,
            ];
        }

        $output = trim($process->output());
        $decoded = json_decode($output, true);

        if (! is_array($decoded)) {
            return [
                'success' => false,
                'result' => null,
                'error' => 'Unable to parse the claude command output.',
                'session_id' => null,
                'raw' => $output,
            ];
        }

        $isError = (bool) ($decoded['is_error'] ?? false);

        return [
            'success' => ! $isError,
            'result' => $decoded['result'] ?? null,
            'error' => $isError ? ($decoded['result'] ?? 'The claude command reported an error.') : null,
            'session_id' => $decoded['session_id'] ?? null,
            'raw' => $decoded,
        ];
    }

    /**
     * Build the argument list for the claude CLI invocation.
     *
     * When resuming a session the persona and model already live in the
     * conversation, so they are only applied when starting a fresh session.
     *
     * @param  array{systemPrompt?: ?string, promptMode?: string, model?: ?string, sessionId?: ?string}  $options
     * @return list<string>
     */
    protected function buildCommand(string $prompt, array $options): array
    {
        $command = [
            config('services.claude_cli.binary'),
            '-p', $prompt,
            '--output-format', 'json',
        ];

        $permissionMode = config('services.claude_cli.permission_mode');

        if ($permissionMode !== null && $permissionMode !== '') {
            $command[] = '--permission-mode';
            $command[] = $permissionMode;
        }

        $sessionId = $options['sessionId'] ?? null;

        if ($sessionId !== null && $sessionId !== '') {
            $command[] = '--resume';
            $command[] = $sessionId;

            return $command;
        }

        $systemPrompt = $options['systemPrompt'] ?? null;

        if ($systemPrompt !== null && $systemPrompt !== '') {
            $flag = ($options['promptMode'] ?? 'append') === 'override'
                ? '--system-prompt'
                : '--append-system-prompt';

            $command[] = $flag;
            $command[] = $systemPrompt;
        }

        $model = $options['model'] ?? null;

        if ($model !== null && $model !== '') {
            $command[] = '--model';
            $command[] = $model;
        }

        return $command;
    }
}
