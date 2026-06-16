<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'plan',
        'status',
        'stripe_subscription_id',
        'stripe_customer_id',
        'current_period_start',
        'current_period_end',
        'trial_ends_at',
        'analyses_used',
        'analyses_limit',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end'   => 'datetime',
        'trial_ends_at'        => 'datetime',
        'analyses_used'        => 'integer',
        'analyses_limit'       => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function hasAnalysesRemaining(): bool
    {
        if ($this->analyses_limit === -1) {
            return true; // unlimited
        }

        return $this->analyses_used < $this->analyses_limit;
    }

    public function incrementAnalysisCount(): void
    {
        $this->increment('analyses_used');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }
}
