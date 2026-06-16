<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organization\Models\Organization;
use App\Domain\Repository\Models\Project;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use ApiResponse;

    public function index(Request $request, Organization $organization): JsonResponse
    {
        $projects = Project::where('organization_id', $organization->id)
            ->with(['repository', 'team', 'latestAnalysis'])
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(20);

        return $this->paginated($projects);
    }

    public function store(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'repository_id' => ['required', 'uuid', 'exists:repositories,id'],
            'team_id'       => ['nullable', 'uuid', 'exists:teams,id'],
            'type'          => ['required', 'in:laravel,flutter'],
            'default_branch' => ['sometimes', 'string', 'max:255'],
            'environment'   => ['sometimes', 'in:production,staging,development'],
        ]);

        $project = Project::create([
            ...$request->validated(),
            'organization_id' => $organization->id,
        ]);

        return $this->success(
            ['project' => $project->load(['repository', 'team'])],
            'Project created',
            201
        );
    }

    public function show(Organization $organization, Project $project): JsonResponse
    {
        return $this->success([
            'project' => $project->load(['repository', 'team', 'latestAnalysis']),
        ]);
    }

    public function update(Request $request, Organization $organization, Project $project): JsonResponse
    {
        $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'team_id'     => ['nullable', 'uuid', 'exists:teams,id'],
            'environment' => ['sometimes', 'in:production,staging,development'],
            'settings'    => ['nullable', 'array'],
        ]);

        $project->update($request->validated());

        return $this->success(['project' => $project->fresh()]);
    }

    public function destroy(Organization $organization, Project $project): JsonResponse
    {
        $project->delete();

        return $this->success(message: 'Project deleted');
    }
}
