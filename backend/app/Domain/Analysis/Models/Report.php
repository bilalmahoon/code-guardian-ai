<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'analysis_id',
        'organization_id',
        'format',
        'storage_path',
        'summary',
        'expires_at',
        'download_count',
        'created_at',
    ];

    protected $casts = [
        'summary'        => 'array',
        'expires_at'     => 'datetime',
        'created_at'     => 'datetime',
        'download_count' => 'integer',
    ];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
