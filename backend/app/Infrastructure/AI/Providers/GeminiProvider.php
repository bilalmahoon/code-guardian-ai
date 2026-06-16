<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Providers;

use App\Infrastructure\AI\Contracts\AiProviderContract;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiProvider implements AiProviderContract
{
    private string $model;
    private string $apiKey;
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->model  = config('services.gemini.model', 'gemini-1.5-pro');
        $this->apiKey = config('services.gemini.key', '');
    }

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): string
    {
        $model    = $options['model'] ?? $this->model;
        $response = Http::timeout(120)
            ->post(self::BASE_URL . "/{$model}:generateContent?key={$this->apiKey}", [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => [
                    ['parts' => [['text' => $userPrompt]]],
                ],
                'generationConfig' => [
                    'temperature'     => $options['temperature'] ?? 0.2,
                    'maxOutputTokens' => $options['max_tokens'] ?? 4096,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    public function stream(string $systemPrompt, string $userPrompt, callable $onChunk, array $options = []): void
    {
        // Simplified: use non-streaming for now
        $text = $this->complete($systemPrompt, $userPrompt, $options);
        $onChunk($text);
    }

    public function embed(string $text): array
    {
        $response = Http::post(
            self::BASE_URL . "/embedding-001:embedContent?key={$this->apiKey}",
            ['content' => ['parts' => [['text' => $text]]]]
        );

        $response->throw();

        return $response->json('embedding.values', []);
    }

    public function getProviderName(): string
    {
        return 'gemini';
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
