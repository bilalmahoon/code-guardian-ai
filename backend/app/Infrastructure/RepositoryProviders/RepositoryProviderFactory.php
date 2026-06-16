<?php

declare(strict_types=1);

namespace App\Infrastructure\RepositoryProviders;

use App\Infrastructure\RepositoryProviders\Contracts\RepositoryProviderContract;
use InvalidArgumentException;

class RepositoryProviderFactory
{
    /** @var array<string, class-string<RepositoryProviderContract>> */
    private static array $adapters = [
        'bitbucket' => BitbucketAdapter::class,
        'github'    => GithubAdapter::class,
        'gitlab'    => GitlabAdapter::class,
    ];

    public static function make(string $provider): RepositoryProviderContract
    {
        if (! isset(self::$adapters[$provider])) {
            throw new InvalidArgumentException("Unsupported repository provider: {$provider}");
        }

        return app(self::$adapters[$provider]);
    }

    public static function register(string $provider, string $adapterClass): void
    {
        self::$adapters[$provider] = $adapterClass;
    }

    public static function supported(): array
    {
        return array_keys(self::$adapters);
    }
}
