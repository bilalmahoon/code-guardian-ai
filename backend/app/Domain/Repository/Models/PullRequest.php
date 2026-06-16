<?php

declare(strict_types=1);

namespace App\Domain\Repository\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PullRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'repository_id',
        'provider_pr_id',
        'title',
        'source_branch',
        'target_branch',
        'author',
        'status',
        'diff_url',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isMerged(): bool
    {
        return $this->status === 'merged';
    }
}
