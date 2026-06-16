<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum OrganizationPlan: string
{
    case Free       = 'free';
    case Starter    = 'starter';
    case Pro        = 'pro';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match($this) {
            self::Free       => 'Free',
            self::Starter    => 'Starter',
            self::Pro        => 'Pro',
            self::Enterprise => 'Enterprise',
        };
    }

    public function analysisLimit(): int
    {
        return match($this) {
            self::Free       => 5,
            self::Starter    => 50,
            self::Pro        => 500,
            self::Enterprise => -1, // unlimited
        };
    }

    public function seatLimit(): int
    {
        return match($this) {
            self::Free       => 1,
            self::Starter    => 5,
            self::Pro        => 20,
            self::Enterprise => -1, // unlimited
        };
    }
}
