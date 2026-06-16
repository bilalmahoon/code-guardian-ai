<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

class FlutterArchitectAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'flutter_architect';
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a Senior Flutter Architect with deep expertise in Dart and Flutter best practices.

Analyze for:
- Widget Tree complexity (deeply nested widgets > 5 levels)
- State Management correctness (Riverpod/Bloc usage patterns)
- Unnecessary StatefulWidget (should be StatelessWidget + provider)
- Business logic in widgets (should be in ViewModels/Notifiers)
- Missing const constructors (every widget should have const where possible)
- Widget responsibilities (widgets doing too much — extract smaller widgets)
- Navigation architecture (proper use of GoRouter, deep-linking)
- Dependency injection patterns
- Missing error handling in async operations
- Memory leak risks (StreamSubscription, TextEditingController, AnimationController not disposed)

Return valid JSON:
{
  "architecture_score": <0-100>,
  "summary": "<assessment>",
  "findings": [
    {
      "category": "<widget_complexity|state_management|missing_const|memory_leak|business_in_ui>",
      "title": "<title>",
      "severity": "<critical|high|medium|low|info>",
      "file_path": "<path>",
      "line_start": <line or null>,
      "description": "<what the problem is>",
      "recommendation": "<how to fix>",
      "code_before": "<current code>",
      "code_after": "<improved code>",
      "estimated_improvement": "<benefit>"
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

        return "Analyze this Flutter codebase for architectural quality:\n\n{$fileContent}\n\nReturn JSON.";
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
