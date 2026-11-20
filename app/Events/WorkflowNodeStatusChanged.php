<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcast when a workflow node's run status changes, so the editor canvas can
 * reflect it live. Sent immediately (ShouldBroadcastNow) because it is fired
 * from within the already-queued node execution job.
 */
class WorkflowNodeStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public int $workflowId,
        public int $nodeId,
        public string $status,
    ) {}

    /**
     * Listeners are scoped to the owning workflow's private channel.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('workflows.'.$this->workflowId),
        ];
    }

    /**
     * Fixed broadcast name so the channel does not depend on the payload.
     */
    public function broadcastAs(): string
    {
        return 'node.status';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'nodeId' => $this->nodeId,
            'status' => $this->status,
        ];
    }
}
