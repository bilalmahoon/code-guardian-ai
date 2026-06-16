<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum IssueSeverity: string
{
    case Critical = 'critical';
    case High     = 'high';
    case Medium   = 'medium';
    case Low      = 'low';
    case Info     = 'info';

    public function score(): int
    {
        return match($this) {
            self::Critical => 5,
            self::High     => 4,
            self::Medium   => 3,
            self::Low      => 2,
            self::Info     => 1,
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Critical => '#dc2626',
            self::High     => '#ea580c',
            self::Medium   => '#ca8a04',
            self::Low      => '#2563eb',
            self::Info     => '#6b7280',
        };
    }
}
