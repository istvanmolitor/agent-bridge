<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class ClaudeCli
{
    /**
     * Run a prompt through the locally installed `claude` CLI and return its parsed response.
     *
     * @return array{success: bool, result: ?string, error: ?string, raw: mixed}
     */
    public function run(string $prompt): array
    {
        $workdir = config('services.claude_cli.workdir');

        File::ensureDirectoryExists($workdir);

        $process = Process::path($workdir)
            ->timeout((int) config('services.claude_cli.timeout'))
            ->run([
                config('services.claude_cli.binary'),
                '-p', $prompt,
                '--output-format', 'json',
            ]);

        if (! $process->successful()) {
            $errorOutput = trim($process->errorOutput());

            return [
                'success' => false,
                'result' => null,
                'error' => $errorOutput !== '' ? $errorOutput : 'The claude command exited with an error.',
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
                'raw' => $output,
            ];
        }

        $isError = (bool) ($decoded['is_error'] ?? false);

        return [
            'success' => ! $isError,
            'result' => $decoded['result'] ?? null,
            'error' => $isError ? ($decoded['result'] ?? 'The claude command reported an error.') : null,
            'raw' => $decoded,
        ];
    }
}
