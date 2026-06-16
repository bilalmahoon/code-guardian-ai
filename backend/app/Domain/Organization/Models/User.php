<?php

declare(strict_types=1);

namespace App\Domain\Organization\Models;

use App\Domain\Subscription\Models\Subscription;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasUuids, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'github_id',
        'google_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'github_id',
        'google_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'password'          => 'hashed',
    ];

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'created_by');
    }

    public function isOAuthOnly(): bool
    {
        return is_null($this->password);
    }

    public function getOrganizationRole(Organization $organization): ?string
    {
        $pivot = $this->organizations()
            ->where('organizations.id', $organization->id)
            ->first()?->pivot;

        return $pivot?->role;
    }
}
