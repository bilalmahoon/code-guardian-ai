<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Contracts;

interface AiProviderContract
{
    /**
     * Send a completion request and return the full response text.
     */
    public function complete(string $systemPrompt, string $userPrompt, array $options = []): string;

    /**
     * Send a completion request and stream the response.
     *
     * @param callable $onChunk Called with each text chunk as it arrives
     */
    public function stream(string $systemPrompt, string $userPrompt, callable $onChunk, array $options = []): void;

    /**
     * Generate embeddings for the given text.
     *
     * @return float[]
     */
    public function embed(string $text): array;

    /**
     * Return the provider name (openai, claude, gemini).
     */
    public function getProviderName(): string;

    /**
     * Return the model being used.
     */
    public function getModel(): string;

    /**
     * Return approximate token count for a string.
     */
    public function estimateTokens(string $text): int;
}
