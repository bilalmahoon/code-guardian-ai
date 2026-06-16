<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

use App\Infrastructure\AI\AiProviderFactory;
use App\Infrastructure\AI\Contracts\AiProviderContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

abstract class BaseAgent
{
    protected AiProviderContract $provider;

    public function __construct()
    {
        $this->provider = AiProviderFactory::default();
    }

    abstract public function getName(): string;

    abstract protected function getSystemPrompt(): string;

    /**
     * Run the agent with the given code context.
     * Returns structured findings array.
     */
    abstract public function analyze(array $context): array;

    /**
     * Build the user prompt from the context.
     */
    abstract protected function buildUserPrompt(array $context): string;

    /**
     * Parse the AI response into structured findings.
     */
    abstract protected function parseResponse(string $response): array;

    /**
     * Run the agent, handling retries and errors.
     */
    protected function run(array $context): array
    {
        $userPrompt = $this->buildUserPrompt($context);

        try {
            $response = $this->provider->complete(
                $this->getSystemPrompt(),
                $userPrompt,
                $this->getOptions()
            );

            return $this->parseResponse($response);
        } catch (\Throwable $e) {
            Log::error("[{$this->getName()}] Analysis failed", [
                'error'       => $e->getMessage(),
                'analysis_id' => $context['analysis_id'] ?? null,
            ]);

            return ['error' => $e->getMessage(), 'findings' => []];
        }
    }

    protected function getOptions(): array
    {
        return [
            'temperature' => 0.1,
            'max_tokens'  => 4096,
        ];
    }

    /**
     * Extract JSON from an AI response that may contain markdown code fences.
     */
    protected function extractJson(string $response): array
    {
        // Strip markdown code fences if present
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $response);
        $cleaned = preg_replace('/^```\s*$/m', '', $cleaned);
        $cleaned = trim($cleaned ?? $response);

        $decoded = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to find JSON block within the response
            if (preg_match('/\{[\s\S]+\}/m', $cleaned, $matches)) {
                $decoded = json_decode($matches[0], true);
            }
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Load a prompt template from resources/prompts/.
     */
    protected function loadPrompt(string $name): string
    {
        $path = resource_path("prompts/{$name}.md");

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return '';
    }

    /**
     * Truncate code context to fit within token limits.
     */
    protected function truncateContext(string $content, int $maxTokens = 50000): string
    {
        $estimated = $this->provider->estimateTokens($content);

        if ($estimated <= $maxTokens) {
            return $content;
        }

        // Truncate to approximate token limit
        $ratio = $maxTokens / $estimated;
        $chars = (int) (strlen($content) * $ratio * 0.9);

        return substr($content, 0, $chars) . "\n\n[... content truncated for token limits ...]";
    }
}
