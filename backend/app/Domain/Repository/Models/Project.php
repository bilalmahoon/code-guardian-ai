<?php

declare(strict_types=1);

namespace App\Domain\Repository\Models;

use App\Domain\Analysis\Models\Analysis;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\Team;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'team_id',
        'repository_id',
        'name',
        'description',
        'type',
        'default_branch',
        'environment',
        'settings',
        'last_analyzed_at',
    ];

    protected $casts = [
        'settings'         => 'array',
        'last_analyzed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }

    public function latestAnalysis(): HasOne
    {
        return $this->hasOne(Analysis::class)->latestOfMany();
    }

    public function isLaravel(): bool
    {
        return $this->type === 'laravel';
    }

    public function isFlutter(): bool
    {
        return $this->type === 'flutter';
    }
}
