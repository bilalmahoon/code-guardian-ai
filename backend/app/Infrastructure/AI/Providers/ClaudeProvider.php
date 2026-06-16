<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Providers;

use App\Infrastructure\AI\Contracts\AiProviderContract;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeProvider implements AiProviderContract
{
    private string $model;
    private string $apiKey;
    private const BASE_URL     = 'https://api.anthropic.com/v1';
    private const API_VERSION  = '2023-06-01';

    public function __construct()
    {
        $this->model  = config('services.anthropic.model', 'claude-3-5-sonnet-20241022');
        $this->apiKey = config('services.anthropic.key', '');
    }

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): string
    {
        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => self::API_VERSION,
            'content-type'      => 'application/json',
        ])->timeout(120)->post(self::BASE_URL . '/messages', [
            'model'      => $options['model'] ?? $this->model,
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Anthropic API error: ' . $response->body());
        }

        return $response->json('content.0.text', '');
    }

    public function stream(string $systemPrompt, string $userPrompt, callable $onChunk, array $options = []): void
    {
        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => self::API_VERSION,
        ])->timeout(120)->post(self::BASE_URL . '/messages', [
            'model'      => $options['model'] ?? $this->model,
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'stream'     => true,
        ]);

        foreach (explode("\n", $response->body()) as $line) {
            if (str_starts_with($line, 'data: ')) {
                $data = json_decode(substr($line, 6), true);
                if (($data['type'] ?? '') === 'content_block_delta') {
                    $onChunk($data['delta']['text'] ?? '');
                }
            }
        }
    }

    public function embed(string $text): array
    {
        // Claude doesn't have a dedicated embeddings endpoint
        // Fall back to a simple hash-based embedding for now
        return [];
    }

    public function getProviderName(): string
    {
        return 'claude';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}
