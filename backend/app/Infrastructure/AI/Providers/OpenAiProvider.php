<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Providers;

use App\Infrastructure\AI\Contracts\AiProviderContract;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiProvider implements AiProviderContract
{
    private string $model;
    private string $apiKey;
    private const BASE_URL = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->model  = config('services.openai.model', 'gpt-4o');
        $this->apiKey = config('services.openai.key', '');
    }

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->post(self::BASE_URL . '/chat/completions', array_merge([
                'model'       => $options['model'] ?? $this->model,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature' => $options['temperature'] ?? 0.2,
                'max_tokens'  => $options['max_tokens'] ?? 4096,
            ], $options));

        if ($response->failed()) {
            throw new RuntimeException('OpenAI API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

    public function stream(string $systemPrompt, string $userPrompt, callable $onChunk, array $options = []): void
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->withOptions(['stream' => true])
            ->post(self::BASE_URL . '/chat/completions', [
                'model'       => $options['model'] ?? $this->model,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature' => $options['temperature'] ?? 0.2,
                'stream'      => true,
            ]);

        foreach (explode("\n", $response->body()) as $line) {
            if (str_starts_with($line, 'data: ') && $line !== 'data: [DONE]') {
                $data = json_decode(substr($line, 6), true);
                if ($chunk = $data['choices'][0]['delta']['content'] ?? null) {
                    $onChunk($chunk);
                }
            }
        }
    }

    public function embed(string $text): array
    {
        $response = Http::withToken($this->apiKey)
            ->post(self::BASE_URL . '/embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

        $response->throw();

        return $response->json('data.0.embedding', []);
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function estimateTokens(string $text): int
    {
        // Rough estimate: ~4 chars per token for English text
        return (int) ceil(strlen($text) / 4);
    }
}
