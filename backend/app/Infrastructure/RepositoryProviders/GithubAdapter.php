<?php

declare(strict_types=1);

namespace App\Infrastructure\RepositoryProviders;

use App\Domain\Repository\Models\Repository;
use App\Infrastructure\RepositoryProviders\Contracts\RepositoryProviderContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class GithubAdapter implements RepositoryProviderContract
{
    private const BASE_URL  = 'https://api.github.com';
    private const AUTH_URL  = 'https://github.com/login/oauth/authorize';
    private const TOKEN_URL = 'https://github.com/login/oauth/access_token';

    public function getProviderName(): string
    {
        return 'github';
    }

    public function listRepositories(string $accessToken): Collection
    {
        $repos = collect();
        $page  = 1;

        while (true) {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                ->get(self::BASE_URL . "/user/repos?per_page=100&page={$page}&sort=updated");

            $response->throw();

            $data = $response->json();

            if (empty($data)) break;

            foreach ($data as $repo) {
                $repos->push([
                    'provider'         => 'github',
                    'provider_repo_id' => (string) $repo['id'],
                    'full_name'        => $repo['full_name'],
                    'clone_url'        => $repo['clone_url'],
                    'default_branch'   => $repo['default_branch'],
                    'is_private'       => $repo['private'],
                    'description'      => $repo['description'],
                ]);
            }

            if (count($data) < 100) break;
            $page++;
        }

        return $repos;
    }

    public function listBranches(string $accessToken, string $repoId): Collection
    {
        [$owner, $repo] = explode('/', $repoId);
        $response = Http::withToken($accessToken)
            ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
            ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/branches?per_page=100");

        $response->throw();

        return collect($response->json())->map(fn($b) => [
            'name'   => $b['name'],
            'target' => $b['commit']['sha'] ?? null,
        ]);
    }

    public function listPullRequests(string $accessToken, string $repoId): Collection
    {
        [$owner, $repo] = explode('/', $repoId);
        $response = Http::withToken($accessToken)
            ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
            ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/pulls?state=open&per_page=50");

        $response->throw();

        return collect($response->json())->map(fn($pr) => [
            'provider_pr_id' => (string) $pr['number'],
            'title'          => $pr['title'],
            'source_branch'  => $pr['head']['ref'],
            'target_branch'  => $pr['base']['ref'],
            'author'         => $pr['user']['login'] ?? null,
            'status'         => 'open',
        ]);
    }

    public function cloneRepository(Repository $repository, string $accessToken, string $targetPath): string
    {
        $cloneUrl = str_replace('https://', "https://x-token-auth:{$accessToken}@", $repository->clone_url);
        $command  = "git clone --depth=1 " . escapeshellarg($cloneUrl) . " " . escapeshellarg($targetPath);
        exec($command . " 2>&1", $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Failed to clone: ' . implode("\n", $output));
        }

        return $targetPath;
    }

    public function getFileTree(string $accessToken, string $repoId, string $ref = 'HEAD'): array
    {
        [$owner, $repo] = explode('/', $repoId);
        $response = Http::withToken($accessToken)
            ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
            ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/git/trees/{$ref}?recursive=1");

        $response->throw();

        return $response->json('tree', []);
    }

    public function registerWebhook(Repository $repository, string $accessToken, string $webhookUrl): string
    {
        [$owner, $repo] = explode('/', $repository->full_name);
        $response = Http::withToken($accessToken)
            ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
            ->post(self::BASE_URL . "/repos/{$owner}/{$repo}/hooks", [
                'name'   => 'web',
                'active' => true,
                'events' => ['push', 'pull_request'],
                'config' => [
                    'url'          => $webhookUrl,
                    'content_type' => 'json',
                    'insecure_ssl' => '0',
                ],
            ]);

        $response->throw();

        return (string) $response->json('id');
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    public function getAuthorizationUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => config('services.github.client_id'),
            'scope'     => 'repo,read:user,user:email',
            'state'     => $state,
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::withHeaders(['Accept' => 'application/json'])
            ->post(self::TOKEN_URL, [
                'client_id'     => config('services.github.client_id'),
                'client_secret' => config('services.github.client_secret'),
                'code'          => $code,
            ]);

        $response->throw();

        return $response->json();
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        // GitHub personal access tokens don't expire by default
        return ['access_token' => $refreshToken];
    }
}
