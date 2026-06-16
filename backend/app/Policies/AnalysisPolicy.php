<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Analysis\Models\Analysis;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\User;

class AnalysisPolicy
{
    public function view(User $user, Analysis $analysis, Organization $organization): bool
    {
        return $organization->hasMember($user)
            && $analysis->organization_id === $organization->id;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $organization->hasMember($user);
    }

    public function update(User $user, Analysis $analysis, Organization $organization): bool
    {
        return $organization->hasMember($user)
            && $analysis->organization_id === $organization->id;
    }

    public function cancel(User $user, Analysis $analysis, Organization $organization): bool
    {
        return $this->update($user, $analysis, $organization)
            || $analysis->triggered_by === $user->id;
    }
}
