<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

class LaravelArchitectAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'laravel_architect';
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a Senior Laravel Architect with 10+ years of experience. Your task is to analyze Laravel PHP code for architectural quality.

Analyze for:
- SOLID Principles violations (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion)
- Fat Controllers (controllers doing too much business logic)
- Fat Models (models with too many responsibilities)
- Service Layer absence or misuse
- Repository Pattern implementation quality
- Dependency Injection correctness
- Missing abstractions
- Tight coupling between components
- Improper use of Laravel features (Events, Jobs, Observers, Policies)

For each finding, provide:
1. A clear title
2. Severity (critical, high, medium, low, info)
3. File path and approximate line number
4. Detailed description of the problem
5. Root cause analysis
6. Concrete recommended fix with before/after code examples

Return your analysis as a valid JSON object matching this structure:
{
  "architecture_score": <0-100 integer>,
  "summary": "<brief overall assessment>",
  "findings": [
    {
      "category": "<e.g. fat_controller, solid_violation, missing_service_layer>",
      "title": "<concise issue title>",
      "severity": "<critical|high|medium|low|info>",
      "file_path": "<relative file path>",
      "line_start": <line number or null>,
      "description": "<detailed description>",
      "root_cause": "<why this is a problem>",
      "recommendation": "<how to fix it>",
      "code_before": "<problematic code snippet>",
      "code_after": "<improved code snippet>",
      "estimated_improvement": "<what the fix achieves>"
    }
  ]
}
PROMPT;
    }

    protected function buildUserPrompt(array $context): string
    {
        $projectType = $context['project_type'] ?? 'laravel';
        $files       = $context['files'] ?? [];
        $fileContent = '';

        foreach ($files as $path => $content) {
            $fileContent .= "\n\n=== FILE: {$path} ===\n{$content}";
        }

        $fileContent = $this->truncateContext($fileContent);

        return <<<PROMPT
Please analyze the following Laravel codebase for architectural quality.

Project Type: {$projectType}
Analysis ID: {$context['analysis_id']}

FILES TO ANALYZE:
{$fileContent}

Provide your analysis as a JSON object following the specified structure.
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
            'agent'              => $this->getName(),
            'architecture_score' => $data['architecture_score'] ?? null,
            'summary'            => $data['summary'] ?? '',
            'findings'           => $data['findings'] ?? [],
        ];
    }
}
