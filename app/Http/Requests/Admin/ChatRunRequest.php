<?php

namespace App\Http\Requests\Admin;

use App\Models\AgentSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatRunRequest extends FormRequest
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
            'agent_session_id' => [
                'required',
                Rule::exists(AgentSession::class, 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
