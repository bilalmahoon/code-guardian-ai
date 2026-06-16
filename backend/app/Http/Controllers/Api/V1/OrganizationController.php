<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organization\Actions\CreateOrganizationAction;
use App\Domain\Organization\DTOs\CreateOrganizationDTO;
use App\Domain\Organization\Models\Organization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\CreateOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\Organization\OrganizationResource;
use App\Http\Resources\Organization\OrganizationMemberResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CreateOrganizationAction $createOrganization,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $organizations = $request->user()
            ->organizations()
            ->with(['subscription'])
            ->orderBy('organization_users.joined_at', 'desc')
            ->get();

        return $this->success([
            'organizations' => OrganizationResource::collection($organizations),
        ]);
    }

    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $organization = ($this->createOrganization)(
            CreateOrganizationDTO::fromRequest($request),
            $request->user()
        );

        return $this->success([
            'organization' => new OrganizationResource($organization),
        ], 'Organization created successfully', 201);
    }

    public function show(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        return $this->success([
            'organization' => new OrganizationResource(
                $organization->load(['teams', 'subscription'])
            ),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $organization->update($request->validated());

        return $this->success([
            'organization' => new OrganizationResource($organization->fresh()),
        ], 'Organization updated successfully');
    }

    public function members(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        $members = $organization->members()
            ->orderByPivot('joined_at', 'desc')
            ->get();

        return $this->success([
            'members' => OrganizationMemberResource::collection($members),
        ]);
    }

    public function inviteMember(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('manage-members', $organization);

        $request->validate([
            'email' => ['required', 'email'],
            'role'  => ['required', 'in:org_admin,eng_manager,developer,qa_engineer'],
        ]);

        // Find or create user
        $user = \App\Domain\Organization\Models\User::firstOrCreate(
            ['email' => $request->email],
            ['name'  => explode('@', $request->email)[0]]
        );

        if ($organization->hasMember($user)) {
            return $this->error('User is already a member of this organization', 422);
        }

        $organization->members()->attach($user->id, [
            'id'        => \Illuminate\Support\Str::uuid(),
            'role'      => $request->role,
            'joined_at' => now(),
        ]);

        // TODO: Send invitation email
        // event(new UserInvited($user, $organization, $request->role));

        return $this->success(message: 'Invitation sent successfully', code: 201);
    }

    public function removeMember(Organization $organization, string $userId): JsonResponse
    {
        $this->authorize('manage-members', $organization);

        $organization->members()->detach($userId);

        return $this->success(message: 'Member removed successfully');
    }
}
