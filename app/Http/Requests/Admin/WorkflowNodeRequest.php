<?php

namespace App\Http\Requests\Admin;

use App\Models\WorkflowNode;
use App\Workflows\NodeRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowNodeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'position_x' => ['nullable', 'numeric'],
            'position_y' => ['nullable', 'numeric'],
        ];

        // The type is fixed when the node is created and cannot change afterwards.
        if ($this->isMethod('post')) {
            $rules['type'] = ['required', Rule::in(NodeRegistry::keys())];
        }

        // Settings are edited after creation. Each node type declares the rules
        // for its own settings; everything else on the column is rejected.
        if ($this->isMethod('put')) {
            $rules['settings'] = ['nullable', 'array'];

            $node = $this->route('node');
            $class = NodeRegistry::classFor($node instanceof WorkflowNode ? $node->type : '');

            foreach ($class ? $class::settingsRules() : [] as $key => $rule) {
                $rules["settings.{$key}"] = $rule;
            }
        }

        return $rules;
    }
}
