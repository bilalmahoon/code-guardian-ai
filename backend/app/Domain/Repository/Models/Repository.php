<?php

declare(strict_types=1);

namespace App\Domain\Repository\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'provider',
        'provider_repo_id',
        'full_name',
        'clone_url',
        'default_branch',
        'is_private',
        'webhook_id',
        'webhook_secret',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'last_synced_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'webhook_secret',
    ];

    protected $casts = [
        'is_private'        => 'boolean',
        'token_expires_at'  => 'datetime',
        'last_synced_at'    => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function pullRequests(): HasMany
    {
        return $this->hasMany(PullRequest::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at?->isPast() ?? false;
    }

    public function getDecryptedAccessToken(): ?string
    {
        return $this->access_token ? decrypt($this->access_token) : null;
    }

    public function getDecryptedRefreshToken(): ?string
    {
        return $this->refresh_token ? decrypt($this->refresh_token) : null;
    }
}
