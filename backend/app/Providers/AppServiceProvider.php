<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Organization\Models\User;
use App\Infrastructure\AI\AiProviderFactory;
use App\Infrastructure\AI\Contracts\AiProviderContract;
use App\Infrastructure\AI\Providers\GeminiProvider;
use App\Infrastructure\AI\Providers\OpenAiProvider;
use App\Infrastructure\AI\Providers\ClaudeProvider;
use App\Infrastructure\RepositoryProviders\BitbucketAdapter;
use App\Infrastructure\RepositoryProviders\Contracts\RepositoryProviderContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind AI providers
        $this->app->bind(OpenAiProvider::class);
        $this->app->bind(ClaudeProvider::class);
        $this->app->bind(GeminiProvider::class);

        // Bind default AI provider from config
        $this->app->bind(AiProviderContract::class, fn() => AiProviderFactory::default());

        // Bind repository providers
        $this->app->bind(BitbucketAdapter::class);
    }

    public function boot(): void
    {
        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Super admin bypasses all policies
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}
