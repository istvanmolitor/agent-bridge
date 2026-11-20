<?php

namespace App\Http\Requests\Admin;

use App\Models\Prompt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromptRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $prompt = $this->route('prompt');
        $promptId = $prompt instanceof Prompt ? $prompt->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('prompts', 'name')
                    ->where('user_id', $this->user()->id)
                    ->ignore($promptId),
            ],
            'content' => ['required', 'string', 'max:20000'],
        ];
    }
}
