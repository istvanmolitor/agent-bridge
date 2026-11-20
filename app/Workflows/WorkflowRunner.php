<?php

namespace App\Workflows;

use App\Jobs\ExecuteWorkflowNode;
use App\Models\WorkflowNode;

/**
 * Entry point for executing workflow nodes.
 *
 * {@see run()} queues a single node for execution together with the parameters
 * it should run with. Once the queued job finishes, it uses this same service
 * to start the node's children, passing along the execute() return value.
 */
class WorkflowRunner
{
    /**
     * Queue a node for execution with the given parameters.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function run(WorkflowNode $node, array $parameters = []): void
    {
        ExecuteWorkflowNode::dispatch($node->id, $parameters);
    }
}
