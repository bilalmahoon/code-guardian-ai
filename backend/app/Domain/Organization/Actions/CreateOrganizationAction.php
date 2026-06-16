<?php

declare(strict_types=1);

namespace App\Domain\Organization\Actions;

use App\Domain\Organization\DTOs\CreateOrganizationDTO;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\User;
use App\Domain\Subscription\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrganizationAction
{
    public function __invoke(CreateOrganizationDTO $dto, User $creator): Organization
    {
        return DB::transaction(function () use ($dto, $creator) {
            $organization = Organization::create([
                'name'       => $dto->name,
                'slug'       => $this->generateSlug($dto->name),
                'created_by' => $creator->id,
            ]);

            // Add creator as org admin
            $organization->members()->attach($creator->id, [
                'id'        => Str::uuid(),
                'role'      => 'org_admin',
                'joined_at' => now(),
            ]);

            // Create free subscription
            Subscription::create([
                'organization_id' => $organization->id,
                'plan'            => 'free',
                'status'          => 'active',
                'analyses_limit'  => 5,
            ]);

            return $organization->load(['subscription']);
        });
    }

    private function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
