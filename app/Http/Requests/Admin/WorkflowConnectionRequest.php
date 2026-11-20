<?php

namespace App\Http\Requests\Admin;

use App\Models\Workflow;
use App\Models\WorkflowNode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
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

    /**
     * Enforce the node types' input/output capabilities once the referenced
     * nodes are known to be valid (e.g. a StartNode cannot be a target).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $source = WorkflowNode::find($this->integer('source_node_id'));
            $target = WorkflowNode::find($this->integer('target_node_id'));

            if ($source && ! $source->allowsOutput()) {
                $validator->errors()->add('source_node_id', __('This node type has no output.'));
            }

            if ($target && ! $target->allowsInput()) {
                $validator->errors()->add('target_node_id', __('This node type has no input.'));
            }
        });
    }
}
