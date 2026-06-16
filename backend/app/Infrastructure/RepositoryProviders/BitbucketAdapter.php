<?php

declare(strict_types=1);

namespace App\Infrastructure\RepositoryProviders;

use App\Domain\Repository\Models\Repository;
use App\Infrastructure\RepositoryProviders\Contracts\RepositoryProviderContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class BitbucketAdapter implements RepositoryProviderContract
{
    private const BASE_URL    = 'https://api.bitbucket.org/2.0';
    private const AUTH_URL    = 'https://bitbucket.org/site/oauth2/authorize';
    private const TOKEN_URL   = 'https://bitbucket.org/site/oauth2/access_token';

    public function getProviderName(): string
    {
        return 'bitbucket';
    }

    public function listRepositories(string $accessToken): Collection
    {
        $repositories = collect();
        $url          = self::BASE_URL . '/repositories?role=member&pagelen=100';

        while ($url) {
            $response = Http::withToken($accessToken)->get($url);
            $response->throw();

            $data = $response->json();

            foreach ($data['values'] as $repo) {
                $repositories->push([
                    'provider'         => 'bitbucket',
                    'provider_repo_id' => $repo['uuid'],
                    'full_name'        => $repo['full_name'],
                    'clone_url'        => $this->extractCloneUrl($repo),
                    'default_branch'   => $repo['mainbranch']['name'] ?? 'main',
                    'is_private'       => $repo['is_private'],
                    'description'      => $repo['description'] ?? null,
                ]);
            }

            $url = $data['next'] ?? null;
        }

        return $repositories;
    }

    public function listBranches(string $accessToken, string $repoId): Collection
    {
        $response = Http::withToken($accessToken)
            ->get(self::BASE_URL . "/repositories/{$repoId}/refs/branches?pagelen=100");

        $response->throw();

        return collect($response->json('values'))->map(fn($b) => [
            'name'   => $b['name'],
            'target' => $b['target']['hash'] ?? null,
        ]);
    }

    public function listPullRequests(string $accessToken, string $repoId): Collection
    {
        $response = Http::withToken($accessToken)
            ->get(self::BASE_URL . "/repositories/{$repoId}/pullrequests?state=OPEN&pagelen=50");

        $response->throw();

        return collect($response->json('values'))->map(fn($pr) => [
            'provider_pr_id' => (string) $pr['id'],
            'title'          => $pr['title'],
            'source_branch'  => $pr['source']['branch']['name'],
            'target_branch'  => $pr['destination']['branch']['name'],
            'author'         => $pr['author']['display_name'] ?? null,
            'status'         => strtolower($pr['state']),
        ]);
    }

    public function cloneRepository(Repository $repository, string $accessToken, string $targetPath): string
    {
        // Build authenticated clone URL
        $cloneUrl = str_replace(
            'https://',
            "https://x-token-auth:{$accessToken}@",
            $repository->clone_url
        );

        $exitCode = 0;
        $command  = "git clone --depth=1 " . escapeshellarg($cloneUrl) . " " . escapeshellarg($targetPath);

        exec($command . " 2>&1", $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Failed to clone repository: ' . implode("\n", $output));
        }

        return $targetPath;
    }

    public function getFileTree(string $accessToken, string $repoId, string $ref = 'HEAD'): array
    {
        $response = Http::withToken($accessToken)
            ->get(self::BASE_URL . "/repositories/{$repoId}/src/{$ref}/?pagelen=100&q=type=\"commit_file\"");

        $response->throw();

        return $response->json('values', []);
    }

    public function registerWebhook(Repository $repository, string $accessToken, string $webhookUrl): string
    {
        $response = Http::withToken($accessToken)
            ->post(self::BASE_URL . "/repositories/{$repository->provider_repo_id}/hooks", [
                'description' => 'CodeGuardian AI Webhook',
                'url'         => $webhookUrl,
                'active'      => true,
                'events'      => [
                    'pullrequest:created',
                    'pullrequest:updated',
                    'pullrequest:fulfilled',
                    'repo:push',
                ],
            ]);

        $response->throw();

        return $response->json('uuid');
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        // Bitbucket sends X-Hub-Signature: sha256=<hash>
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'client_id'     => config('services.bitbucket.client_id'),
            'response_type' => 'code',
            'state'         => $state,
        ]);

        return self::AUTH_URL . '?' . $params;
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->withBasicAuth(
            config('services.bitbucket.client_id'),
            config('services.bitbucket.client_secret')
        )->post(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'code'       => $code,
        ]);

        $response->throw();

        return $response->json();
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $response = Http::asForm()->withBasicAuth(
            config('services.bitbucket.client_id'),
            config('services.bitbucket.client_secret')
        )->post(self::TOKEN_URL, [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        $response->throw();

        return $response->json();
    }

    private function extractCloneUrl(array $repo): string
    {
        foreach ($repo['links']['clone'] ?? [] as $clone) {
            if ($clone['name'] === 'https') {
                return $clone['href'];
            }
        }

        return $repo['links']['clone'][0]['href'] ?? '';
    }
}
