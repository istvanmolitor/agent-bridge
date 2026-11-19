<?php

namespace App\Models;

use Database\Factories\PromptLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $agent_session_id
 * @property string $prompt
 * @property string|null $response
 * @property bool $success
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['agent_session_id', 'prompt', 'response', 'success', 'error'])]
class PromptLog extends Model
{
    /** @use HasFactory<PromptLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<AgentSession, $this>
     */
    public function agentSession(): BelongsTo
    {
        return $this->belongsTo(AgentSession::class);
    }

    /**
     * Scope the logs to those owned by the given user (through their API keys).
     *
     * @param  Builder<PromptLog>  $query
     */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->whereHas('agentSession', fn (Builder $q) => $q->where('user_id', $userId));
    }
}
