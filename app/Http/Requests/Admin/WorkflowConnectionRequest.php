<?php

namespace App\Http\Requests\Admin;

use App\Models\Workflow;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowConnectionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Workflow $workflow */
        $workflow = $this->route('workflow');

        // A connection may only join two distinct nodes of this workflow.
        $belongsToWorkflow = Rule::exists('workflow_nodes', 'id')
            ->where('workflow_id', $workflow->id);

        return [
            'source_node_id' => ['required', 'integer', $belongsToWorkflow],
            'target_node_id' => ['required', 'integer', 'different:source_node_id', $belongsToWorkflow],
        ];
    }
}
