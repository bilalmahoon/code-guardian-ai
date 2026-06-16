<?php

declare(strict_types=1);

namespace App\Infrastructure\RepositoryProviders\Contracts;

use App\Domain\Repository\Models\Repository;
use Illuminate\Support\Collection;

interface RepositoryProviderContract
{
    /**
     * List all repositories accessible with the given access token.
     */
    public function listRepositories(string $accessToken): Collection;

    /**
     * List branches for a given repository.
     */
    public function listBranches(string $accessToken, string $repoId): Collection;

    /**
     * List open pull requests for a repository.
     */
    public function listPullRequests(string $accessToken, string $repoId): Collection;

    /**
     * Clone the repository and return the local path.
     */
    public function cloneRepository(Repository $repository, string $accessToken, string $targetPath): string;

    /**
     * Get file tree for a specific ref (branch/tag/commit).
     */
    public function getFileTree(string $accessToken, string $repoId, string $ref = 'HEAD'): array;

    /**
     * Register a webhook for the repository.
     * Returns the webhook ID from the provider.
     */
    public function registerWebhook(Repository $repository, string $accessToken, string $webhookUrl): string;

    /**
     * Verify the webhook signature from a provider request.
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool;

    /**
     * Get the OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $state): string;

    /**
     * Exchange authorization code for access token.
     */
    public function exchangeCodeForToken(string $code): array;

    /**
     * Refresh an expired access token.
     */
    public function refreshAccessToken(string $refreshToken): array;

    /**
     * Return the provider name (bitbucket, github, gitlab).
     */
    public function getProviderName(): string;
}
