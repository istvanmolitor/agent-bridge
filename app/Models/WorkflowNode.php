<?php

namespace App\Models;

use App\Workflows\NodeRegistry;
use App\Workflows\Nodes\BaseNode;
use Database\Factories\WorkflowNodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workflow_id
 * @property string $type
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property float $position_x
 * @property float $position_y
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'name', 'description', 'status', 'position_x', 'position_y'])]
class WorkflowNode extends Model
{
    /** @use HasFactory<WorkflowNodeFactory> */
    use HasFactory;

    /** The node is not currently executing. */
    public const STATUS_IDLE = 'idle';

    /** The node is executing on the queue right now. */
    public const STATUS_RUNNING = 'running';

    /**
     * The node type class backing this node, or null when the type is unknown.
     *
     * @return class-string<BaseNode>|null
     */
    public function typeClass(): ?string
    {
        return NodeRegistry::classFor($this->type);
    }

    /**
     * Instantiate the node type bound to this node, or null when unknown.
     */
    public function resolveType(): ?BaseNode
    {
        $class = $this->typeClass();

        return $class ? new $class($this) : null;
    }

    /**
     * The downstream nodes this node is connected to.
     *
     * @return BelongsToMany<WorkflowNode, $this>
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkflowNode::class,
            'workflow_connections',
            'source_node_id',
            'target_node_id',
        );
    }

    /**
     * Whether this node can be the target of a connection (has an input).
     */
    public function allowsInput(): bool
    {
        $class = $this->typeClass();

        return $class ? $class::hasInput() : true;
    }

    /**
     * Whether this node can be the source of a connection (has an output).
     */
    public function allowsOutput(): bool
    {
        $class = $this->typeClass();

        return $class ? $class::hasOutput() : true;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position_x' => 'float',
            'position_y' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
