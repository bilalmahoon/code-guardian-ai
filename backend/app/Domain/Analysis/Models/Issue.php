<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Models;

use App\Support\Enums\IssueSeverity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Issue extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'analysis_id',
        'agent_type',
        'category',
        'title',
        'description',
        'severity',
        'file_path',
        'line_start',
        'line_end',
        'code_snippet',
        'rule_id',
        'is_false_positive',
        'dismissed_at',
        'dismissed_by',
        'created_at',
    ];

    protected $casts = [
        'severity'          => IssueSeverity::class,
        'is_false_positive' => 'boolean',
        'dismissed_at'      => 'datetime',
        'created_at'        => 'datetime',
    ];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    public function recommendation(): HasOne
    {
        return $this->hasOne(Recommendation::class);
    }
}
