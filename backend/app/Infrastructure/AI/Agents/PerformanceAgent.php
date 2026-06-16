<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

class PerformanceAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'performance';
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a Senior Performance Engineer specializing in Laravel and Flutter optimization.

For Laravel, identify:
- N+1 Query Problems (missing eager loading with ->with())
- Missing database indexes (especially on foreign keys and frequently queried columns)
- Heavy queries without pagination
- Missing cache opportunities (Cache::remember for expensive queries)
- Synchronous operations that should be queued
- Large dataset operations without chunking
- Missing query scopes causing full table scans

For Flutter, identify:
- Unnecessary widget rebuilds (setState called too broadly)
- Large build() methods that should be split
- Missing const constructors
- Expensive operations in build() methods
- Large list rendering without ListView.builder
- Memory leaks from unclosed streams or controllers
- Missing RepaintBoundary for complex animations

Return valid JSON:
{
  "performance_score": <0-100>,
  "summary": "<performance assessment>",
  "findings": [
    {
      "category": "<e.g. n_plus_one, missing_index, unnecessary_rebuild>",
      "title": "<issue title>",
      "severity": "<critical|high|medium|low|info>",
      "file_path": "<relative path>",
      "line_start": <line or null>,
      "description": "<what the problem is>",
      "impact": "<performance impact — e.g. adds 50ms per request>",
      "recommendation": "<how to fix>",
      "code_before": "<slow code>",
      "code_after": "<optimized code>",
      "estimated_improvement": "<e.g. 90% reduction in database queries>"
    }
  ]
}
PROMPT;
    }

    protected function buildUserPrompt(array $context): string
    {
        $files       = $context['files'] ?? [];
        $fileContent = '';

        foreach ($files as $path => $content) {
            $fileContent .= "\n\n=== FILE: {$path} ===\n{$content}";
        }

        $fileContent = $this->truncateContext($fileContent);

        return <<<PROMPT
Analyze the following codebase for performance issues.

Project Type: {$context['project_type']}

FILES:
{$fileContent}

Return performance findings as JSON.
PROMPT;
    }

    public function analyze(array $context): array
    {
        return $this->run($context);
    }

    protected function parseResponse(string $response): array
    {
        $data = $this->extractJson($response);

        return [
            'agent'             => $this->getName(),
            'performance_score' => $data['performance_score'] ?? null,
            'summary'           => $data['summary'] ?? '',
            'findings'          => $data['findings'] ?? [],
        ];
    }
}
