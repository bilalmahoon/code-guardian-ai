<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum UserRole: string
{
    case SuperAdmin  = 'super_admin';
    case OrgAdmin    = 'org_admin';
    case EngManager  = 'eng_manager';
    case Developer   = 'developer';
    case QaEngineer  = 'qa_engineer';

    public function label(): string
    {
        return match($this) {
            self::SuperAdmin => 'Super Admin',
            self::OrgAdmin   => 'Organization Admin',
            self::EngManager => 'Engineering Manager',
            self::Developer  => 'Developer',
            self::QaEngineer => 'QA Engineer',
        };
    }

    public function canManageMembers(): bool
    {
        return in_array($this, [self::SuperAdmin, self::OrgAdmin, self::EngManager]);
    }

    public function canTriggerAnalysis(): bool
    {
        return true; // all roles can trigger analysis
    }

    public function canManageSubscriptions(): bool
    {
        return in_array($this, [self::SuperAdmin, self::OrgAdmin]);
    }
}
