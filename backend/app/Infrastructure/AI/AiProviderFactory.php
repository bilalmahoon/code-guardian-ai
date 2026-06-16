<?php

declare(strict_types=1);

namespace App\Infrastructure\AI;

use App\Infrastructure\AI\Contracts\AiProviderContract;
use App\Infrastructure\AI\Providers\ClaudeProvider;
use App\Infrastructure\AI\Providers\GeminiProvider;
use App\Infrastructure\AI\Providers\OpenAiProvider;
use InvalidArgumentException;

class AiProviderFactory
{
    /** @var array<string, class-string<AiProviderContract>> */
    private static array $providers = [
        'openai' => OpenAiProvider::class,
        'claude' => ClaudeProvider::class,
        'gemini' => GeminiProvider::class,
    ];

    /**
     * Create the configured default AI provider.
     */
    public static function default(): AiProviderContract
    {
        $provider = config('services.ai.provider', 'openai');

        return self::make($provider);
    }

    /**
     * Create a specific provider by name.
     */
    public static function make(string $provider): AiProviderContract
    {
        if (! isset(self::$providers[$provider])) {
            throw new InvalidArgumentException("Unsupported AI provider: {$provider}");
        }

        return app(self::$providers[$provider]);
    }

    /**
     * Create a provider with automatic fallback on failure.
     * Tries primary provider first, then falls back to secondary.
     */
    public static function withFallback(string $primary = 'openai', string $fallback = 'claude'): AiProviderContract
    {
        try {
            return self::make($primary);
        } catch (\Throwable) {
            return self::make($fallback);
        }
    }
}
