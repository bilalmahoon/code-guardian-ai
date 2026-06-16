<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'email'              => $this->email,
            'avatar_url'         => $this->avatar_url,
            'email_verified_at'  => $this->email_verified_at?->toISOString(),
            'last_login_at'      => $this->last_login_at?->toISOString(),
            'is_oauth_only'      => $this->isOAuthOnly(),
            'roles'              => $this->getRoleNames(),
            'organizations'      => $this->whenLoaded('organizations', fn () =>
                $this->organizations->map(fn ($org) => [
                    'id'   => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'plan' => $org->plan,
                    'role' => $org->pivot->role,
                ])
            ),
            'created_at'         => $this->created_at->toISOString(),
        ];
    }
}
