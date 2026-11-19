<?php

namespace App\Http\Requests\Admin;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WorkspaceRequest extends FormRequest
{
    /**
     * Normalise the folder before validation: trim surrounding whitespace and
     * slashes so "/project-1/" and "project-1" are treated the same.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('folder')) {
            $this->merge([
                'folder' => trim(trim((string) $this->input('folder')), '/'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'folder' => [
                'required',
                'string',
                'max:4096',
                // Letters, numbers, dashes, dots, underscores and nested
                // slashes only. No absolute paths, no "." / ".." segments.
                'regex:/^[A-Za-z0-9._-]+(?:\/[A-Za-z0-9._-]+)*$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $segments = explode('/', (string) $value);

                    if (in_array('.', $segments, true) || in_array('..', $segments, true)) {
                        $fail(__('The folder may not contain "." or ".." segments.'));
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'folder' => __('folder'),
        ];
    }

    /**
     * The absolute path this workspace resolves to, underneath the base dir.
     */
    public function resolvedPath(): string
    {
        return Workspace::resolvePath((string) $this->validated('folder'));
    }

    /**
     * The attributes to persist on the workspace.
     *
     * @return array{name: string, path: string}
     */
    public function workspaceAttributes(): array
    {
        return [
            'name' => (string) $this->validated('name'),
            'path' => $this->resolvedPath(),
        ];
    }
}
