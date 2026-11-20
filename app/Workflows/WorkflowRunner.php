<?php

namespace App\Workflows;

use App\Jobs\ExecuteWorkflowNode;
use App\Models\WorkflowNode;
use Illuminate\Validation\ValidationException;

/**
 * Entry point for executing workflow nodes.
 *
 * {@see run()} validates the parameters against the node type's rules and only
 * queues the node when they pass. Once the queued job finishes, it uses this
 * same service to start the node's children, passing along the execute()
 * return value.
 */
class WorkflowRunner
{
    /**
     * Validate the parameters and queue the node for execution.
     *
     * Invalid input never reaches the queue: the node is marked "failed" with
     * the validation messages instead, and false is returned.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function run(WorkflowNode $node, array $parameters = []): bool
    {
        try {
            $node->resolveType()?->validate($parameters);
        } catch (ValidationException $exception) {
            $node->updateStatus(
                WorkflowNode::STATUS_FAILED,
                implode("\n", $exception->validator->errors()->all()),
            );

            return false;
        }

        // Clear a stale failure from a previous run so the node no longer
        // shows red while it waits on the queue.
        if ($node->status === WorkflowNode::STATUS_FAILED) {
            $node->updateStatus(WorkflowNode::STATUS_IDLE);
        }

        // Immediate node types (e.g. start, prompt) run synchronously so they
        // execute at once and hand off to their children without a worker; the
        // rest go on the queue.
        $class = $node->typeClass();

        if ($class && ! $class::queued()) {
            // Run in-process so it executes at once and fans out to its children
            // within this same request, without touching the queue.
            (new ExecuteWorkflowNode($node->id, $parameters))->handle($this);
        } else {
            ExecuteWorkflowNode::dispatch($node->id, $parameters);
        }

        return true;
    }
}
