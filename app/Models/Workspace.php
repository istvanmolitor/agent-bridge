<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $path
 * @property-read string $relative_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'path'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    /**
     * The base directory every workspace folder lives under.
     */
    public static function basePath(): string
    {
        return rtrim((string) config('workspaces.base'), '/');
    }

    /**
     * Resolve a user-supplied relative folder to an absolute workspace path
     * underneath the base directory.
     */
    public static function resolvePath(string $folder): string
    {
        return static::basePath().'/'.trim($folder, '/');
    }

    /**
     * The folder relative to the base directory, used to prefill the edit form.
     * Falls back to the raw path for legacy workspaces stored outside the base.
     *
     * @return Attribute<string, never>
     */
    protected function relativePath(): Attribute
    {
        return Attribute::get(fn (): string => Str::startsWith($this->path, static::basePath().'/')
            ? Str::after($this->path, static::basePath().'/')
            : $this->path);
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
     * API keys bound to this workspace through its sessions.
     *
     * @return HasManyThrough<ApiKey, AgentSession, $this>
     */
    public function apiKeys(): HasManyThrough
    {
        return $this->hasManyThrough(ApiKey::class, AgentSession::class);
    }
}
