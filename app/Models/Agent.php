<?php

namespace App\Models;

use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property string|null $avatar_path
 * @property string $system_prompt
 * @property string $prompt_mode
 * @property string|null $model
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $avatar_url
 */
#[Fillable(['name', 'description', 'avatar_path', 'system_prompt', 'prompt_mode', 'model'])]
class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['avatar_url'];

    /**
     * Public URL of the uploaded avatar, if any.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->avatar_path !== null
                ? Storage::disk('public')->url($this->avatar_path)
                : null,
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AgentSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(AgentSession::class);
    }

    /**
     * API keys bound to this agent through its sessions.
     *
     * @return HasManyThrough<ApiKey, AgentSession, $this>
     */
    public function apiKeys(): HasManyThrough
    {
        return $this->hasManyThrough(ApiKey::class, AgentSession::class);
    }
}
