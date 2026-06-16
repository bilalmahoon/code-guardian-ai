<?php

declare(strict_types=1);

namespace App\Http\Resources\Organization;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'logo_url'     => $this->logo_url,
            'plan'         => $this->plan,
            'seats'        => $this->seats,
            'settings'     => $this->settings,
            'members_count' => $this->whenCounted('members'),
            'teams'        => $this->whenLoaded('teams'),
            'subscription' => $this->whenLoaded('subscription', fn() => [
                'plan'            => $this->subscription->plan,
                'status'          => $this->subscription->status,
                'analyses_used'   => $this->subscription->analyses_used,
                'analyses_limit'  => $this->subscription->analyses_limit,
                'trial_ends_at'   => $this->subscription->trial_ends_at?->toISOString(),
            ]),
            'created_at'   => $this->created_at->toISOString(),
        ];
    }
}
