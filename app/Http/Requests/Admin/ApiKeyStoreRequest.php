<?php

namespace App\Http\Requests\Admin;

use App\Models\Agent;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiKeyStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'agent_id' => [
                'required',
                Rule::exists(Agent::class, 'id')->where('user_id', $this->user()->id),
            ],
            'workspace_id' => [
                'required',
                Rule::exists(Workspace::class, 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
