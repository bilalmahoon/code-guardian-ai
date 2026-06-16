<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organization\Models\Organization;
use App\Domain\Repository\Models\Repository;
use App\Http\Controllers\Controller;
use App\Infrastructure\RepositoryProviders\RepositoryProviderFactory;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepositoryController extends Controller
{
    use ApiResponse;

    public function index(Organization $organization): JsonResponse
    {
        $repos = $organization->repositories()->latest()->get();

        return $this->success(['repositories' => $repos]);
    }

    public function store(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'provider'         => ['required', 'in:bitbucket,github,gitlab'],
            'provider_repo_id' => ['required', 'string'],
            'full_name'        => ['required', 'string'],
            'clone_url'        => ['required', 'url'],
            'access_token'     => ['required', 'string'],
            'refresh_token'    => ['nullable', 'string'],
            'default_branch'   => ['sometimes', 'string'],
        ]);

        // Encrypt sensitive values
        $repo = $organization->repositories()->create([
            'provider'         => $request->provider,
            'provider_repo_id' => $request->provider_repo_id,
            'full_name'        => $request->full_name,
            'clone_url'        => $request->clone_url,
            'default_branch'   => $request->input('default_branch', 'main'),
            'is_private'       => $request->boolean('is_private', true),
            'access_token'     => encrypt($request->access_token),
            'refresh_token'    => $request->refresh_token ? encrypt($request->refresh_token) : null,
            'token_expires_at' => $request->token_expires_at,
        ]);

        // Register webhook
        try {
            $provider  = RepositoryProviderFactory::make($request->provider);
            $webhookUrl = route('api.webhooks.handle', [
                'provider'     => $request->provider,
                'repositoryId' => $repo->id,
            ]);
            $webhookId = $provider->registerWebhook($repo, $request->access_token, $webhookUrl);
            $repo->update(['webhook_id' => $webhookId]);
        } catch (\Throwable $e) {
            // Webhook registration is non-critical
        }

        return $this->success(['repository' => $repo], 'Repository connected', 201);
    }

    public function show(Organization $organization, Repository $repo): JsonResponse
    {
        return $this->success(['repository' => $repo->load('projects')]);
    }

    public function destroy(Organization $organization, Repository $repo): JsonResponse
    {
        $repo->delete();

        return $this->success(message: 'Repository disconnected');
    }

    public function sync(Organization $organization, Repository $repo): JsonResponse
    {
        try {
            $provider    = RepositoryProviderFactory::make($repo->provider);
            $accessToken = $repo->getDecryptedAccessToken();

            $branches = $provider->listBranches($accessToken, $repo->provider_repo_id);
            $prs      = $provider->listPullRequests($accessToken, $repo->provider_repo_id);

            // Sync pull requests
            foreach ($prs as $pr) {
                $repo->pullRequests()->updateOrCreate(
                    ['provider_pr_id' => $pr['provider_pr_id']],
                    $pr
                );
            }

            $repo->update(['last_synced_at' => now()]);

            return $this->success([
                'branches_count' => count($branches),
                'prs_count'      => count($prs),
            ], 'Repository synced');

        } catch (\Throwable $e) {
            return $this->error('Sync failed: ' . $e->getMessage());
        }
    }

    public function branches(Organization $organization, Repository $repo): JsonResponse
    {
        try {
            $provider    = RepositoryProviderFactory::make($repo->provider);
            $accessToken = $repo->getDecryptedAccessToken();
            $branches    = $provider->listBranches($accessToken, $repo->provider_repo_id);

            return $this->success(['branches' => $branches]);
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch branches: ' . $e->getMessage());
        }
    }

    public function pullRequests(Organization $organization, Repository $repo): JsonResponse
    {
        $prs = $repo->pullRequests()->latest()->get();

        return $this->success(['pull_requests' => $prs]);
    }
}
