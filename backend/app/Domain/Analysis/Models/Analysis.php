<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Models;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\User;
use App\Domain\Repository\Models\Project;
use App\Support\Enums\AnalysisStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Analysis extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'project_id',
        'triggered_by',
        'trigger_type',
        'source_type',
        'source_ref',
        'status',
        'overall_score',
        'security_score',
        'performance_score',
        'architecture_score',
        'maintainability_score',
        'quality_score',
        'debt_score',
        'devops_score',
        'ai_provider_used',
        'tokens_consumed',
        'processing_ms',
        'agent_statuses',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status'         => AnalysisStatus::class,
        'agent_statuses' => 'array',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    public function generatedTests(): HasMany
    {
        return $this->hasMany(GeneratedTest::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(Report::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === AnalysisStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === AnalysisStatus::Failed;
    }

    public function getDurationInSeconds(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->completed_at->diffInSeconds($this->started_at);
        }

        return null;
    }
}
