<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

class QaAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'qa';
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a Senior QA Engineer and Test Automation expert specializing in Laravel (PHPUnit/Pest) and Flutter (flutter_test).

Generate comprehensive test suites covering:
- Positive cases (happy path)
- Negative cases (invalid inputs, unauthorized access)
- Edge cases (empty data, boundary values, null values)
- Boundary conditions (min/max values, empty collections)

For Laravel, generate:
- Unit Tests: Test individual methods/classes in isolation
- Feature Tests: Test HTTP endpoints end-to-end
- API Tests: Test JSON responses, status codes, authentication

For Flutter, generate:
- Unit Tests: Test business logic, providers, use cases
- Widget Tests: Test UI components render and interact correctly

Each test must be complete, runnable code with proper imports and setup.
Tests should use factories/fakers for test data.
Tests should assert both happy path and failure scenarios.

Return valid JSON:
{
  "summary": "<test coverage assessment>",
  "tests": [
    {
      "type": "<unit|feature|api|widget|integration>",
      "framework": "<phpunit|pest|flutter_test>",
      "class_name": "<TestClassName>",
      "scenario": "<what is being tested>",
      "coverage_area": "<class/method being covered>",
      "test_code": "<complete runnable test code>"
    }
  ]
}
PROMPT;
    }

    protected function buildUserPrompt(array $context): string
    {
        $files        = $context['files'] ?? [];
        $issues       = $context['issues'] ?? [];
        $fileContent  = '';

        foreach ($files as $path => $content) {
            $fileContent .= "\n\n=== FILE: {$path} ===\n{$content}";
        }

        $fileContent = $this->truncateContext($fileContent, 40000);

        $issuesSummary = '';
        if (! empty($issues)) {
            $issuesSummary = "\n\nKNOWN ISSUES TO TEST AGAINST:\n";
            foreach (array_slice($issues, 0, 10) as $issue) {
                $issuesSummary .= "- [{$issue['severity']}] {$issue['title']}\n";
            }
        }

        return <<<PROMPT
Generate comprehensive tests for the following codebase.

Project Type: {$context['project_type']}
{$issuesSummary}

FILES TO TEST:
{$fileContent}

Generate tests covering all critical paths, edge cases, and the identified issues.
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
            'agent'   => $this->getName(),
            'summary' => $data['summary'] ?? '',
            'tests'   => $data['tests'] ?? [],
        ];
    }
}
