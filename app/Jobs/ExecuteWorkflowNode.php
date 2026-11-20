<?php

namespace App\Jobs;

use App\Models\WorkflowNode;
use App\Workflows\WorkflowRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Executes a single workflow node on the queue, then starts its children.
 */
class ExecuteWorkflowNode implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public int $nodeId,
        public array $parameters = [],
    ) {}

    /**
     * Run the node's execute() method and fan out to its child nodes, passing
     * the return value along as their parameters. The parameters were already
     * validated by {@see WorkflowRunner::run()} before the job was queued.
     */
    public function handle(WorkflowRunner $runner): void
    {
        $node = WorkflowNode::with('children')->find($this->nodeId);

        if ($node === null) {
            return;
        }

        $type = $node->resolveType();

        if ($type === null) {
            return;
        }

        // Flip the node to "running" so the editor can reflect it, then make sure
        // it always falls back to "idle" even when execute() throws.
        $node->updateStatus(WorkflowNode::STATUS_RUNNING);

        try {
            $result = $type->execute($this->parameters);
        } finally {
            $node->updateStatus(WorkflowNode::STATUS_IDLE);
        }

        foreach ($node->children as $child) {
            $runner->run($child, $result);
        }
    }
}
