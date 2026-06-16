<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Organization\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        if (! $organization instanceof Organization) {
            $organization = Organization::findOrFail($request->route('organization'));
        }

        if (! $organization->hasMember($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this organization.',
            ], 403);
        }

        // Bind the resolved model back so controllers get the loaded instance
        $request->route()->setParameter('organization', $organization);

        return $next($request);
    }
}
