<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $organization->hasMember($user);
    }

    public function update(User $user, Organization $organization): bool
    {
        return in_array(
            $organization->getMemberRole($user),
            ['org_admin', 'super_admin']
        );
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $organization->getMemberRole($user) === 'org_admin'
            || $user->hasRole('super_admin');
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return in_array(
            $organization->getMemberRole($user),
            ['org_admin', 'eng_manager', 'super_admin']
        );
    }

    public function manageSubscriptions(User $user, Organization $organization): bool
    {
        return in_array(
            $organization->getMemberRole($user),
            ['org_admin', 'super_admin']
        );
    }
}
