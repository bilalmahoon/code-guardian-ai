<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'issue_id',
        'analysis_id',
        'problem_description',
        'root_cause',
        'risk_level',
        'recommended_fix',
        'code_before',
        'code_after',
        'estimated_improvement',
        'status',
        'applied_at',
        'created_at',
    ];

    protected $casts = [
        'applied_at'  => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}
