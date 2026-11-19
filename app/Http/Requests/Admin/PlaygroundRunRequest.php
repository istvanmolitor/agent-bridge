<?php

namespace App\Http\Requests\Admin;

use App\Models\ApiKey;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaygroundRunRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:20000'],
            'api_key_id' => [
                'required',
                Rule::exists(ApiKey::class, 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
