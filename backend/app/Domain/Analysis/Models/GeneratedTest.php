<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedTest extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'analysis_id',
        'issue_id',
        'type',
        'framework',
        'class_name',
        'test_code',
        'scenario',
        'coverage_area',
        'validation_status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }
}
