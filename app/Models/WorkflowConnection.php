<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workflow_id
 * @property int $source_node_id
 * @property int $target_node_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['source_node_id', 'target_node_id'])]
class WorkflowConnection extends Model
{
    /**
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * @return BelongsTo<WorkflowNode, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(WorkflowNode::class, 'source_node_id');
    }

    /**
     * @return BelongsTo<WorkflowNode, $this>
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(WorkflowNode::class, 'target_node_id');
    }
}
