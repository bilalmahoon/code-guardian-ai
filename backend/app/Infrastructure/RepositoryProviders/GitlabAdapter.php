<?php

declare(strict_types=1);

namespace App\Infrastructure\RepositoryProviders;

use App\Domain\Repository\Models\Repository;
use App\Infrastructure\RepositoryProviders\Contracts\RepositoryProviderContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class GitlabAdapter implements RepositoryProviderContract
{
    private const BASE_URL  = 'https://gitlab.com/api/v4';
    private const AUTH_URL  = 'https://gitlab.com/oauth/authorize';
    private const TOKEN_URL = 'https://gitlab.com/oauth/token';

    public function getProviderName(): string
    {
        return 'gitlab';
    }

    public function listRepositories(string $accessToken): Collection
    {
        $response = Http::withToken($accessToken)
            ->get(self::BASE_URL . '/projects?membership=true&per_page=100');

        $response->throw();

        return collect($response->json())->map(fn($repo) => [
            'provider'         => 'gitlab',
            'provider_repo_id' => (string) $repo['id'],
            'full_name'        => $repo['path_with_namespace'],
            'clone_url'        => $repo['http_url_to_repo'],
            'default_branch'   => $repo['default_branch'] ?? 'main',
            'is_private'       => $repo['visibility'] !== 'public',
            'description'      => $repo['description'],
        ]);
    }

    public function listBranches(string $accessToken, string $repoId): Collection
    {
        $response = Http::withToken($accessToken)
            ->get(self::BASE_URL . "/projects/{$repoId}/repository/branches?per_page=100");

        $response->throw();

        return collect($response->json())->map(fn($b) => [
            'name'   => $b['name'],
            'target' => $b['commit']['id'] ?? null,
        ]);
    }

    public function listPullRequests(string $accessToken, string $repoId): Collection
    {
        $response = Http::withToken($accessToken)
            ->get(self::BASE_URL . "/projects/{$repoId}/merge_requests?state=opened&per_page=50");

        $response->throw();

        return collect($response->json())->map(fn($mr) => [
            'provider_pr_id' => (string) $mr['iid'],
            'title'          => $mr['title'],
            'source_branch'  => $mr['source_branch'],
            'target_branch'  => $mr['target_branch'],
            'author'         => $mr['author']['username'] ?? null,
            'status'         => 'open',
        ]);
    }

    public function cloneRepository(Repository $repository, string $accessToken, string $targetPath): string
    {
        $cloneUrl = str_replace('https://', "https://oauth2:{$accessToken}@", $repository->clone_url);
        exec("git clone --depth=1 " . escapeshellarg($cloneUrl) . " " . escapeshellarg($targetPath) . " 2>&1", $output, $code);

        if ($code !== 0) {
            throw new \RuntimeException('Clone failed: ' . implode("\n", $output));
        }

        return $targetPath;
    }

    public function getFileTree(string $accessToken, string $repoId, string $ref = 'HEAD'): array
    {
        $response = Http::withToken($accessToken)
            ->get(self::BASE_URL . "/projects/{$repoId}/repository/tree?recursive=true&ref={$ref}&per_page=100");

        $response->throw();

        return $response->json() ?? [];
    }

    public function registerWebhook(Repository $repository, string $accessToken, string $webhookUrl): string
    {
        $response = Http::withToken($accessToken)
            ->post(self::BASE_URL . "/projects/{$repository->provider_repo_id}/hooks", [
                'url'                      => $webhookUrl,
                'push_events'              => true,
                'merge_requests_events'    => true,
                'enable_ssl_verification'  => true,
            ]);

        $response->throw();

        return (string) $response->json('id');
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        return hash_equals($secret, $signature);
    }

    public function getAuthorizationUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => config('services.gitlab.client_id'),
            'redirect_uri'  => config('services.gitlab.redirect'),
            'response_type' => 'code',
            'state'         => $state,
            'scope'         => 'api read_repository',
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::post(self::TOKEN_URL, [
            'client_id'     => config('services.gitlab.client_id'),
            'client_secret' => config('services.gitlab.client_secret'),
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => config('services.gitlab.redirect'),
        ]);

        $response->throw();

        return $response->json();
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $response = Http::post(self::TOKEN_URL, [
            'client_id'     => config('services.gitlab.client_id'),
            'client_secret' => config('services.gitlab.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        $response->throw();

        return $response->json();
    }
}
