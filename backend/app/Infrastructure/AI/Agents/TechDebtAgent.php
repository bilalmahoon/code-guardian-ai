<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

class TechDebtAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'tech_debt';
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a Senior Software Engineer focused on code quality and technical debt reduction.

Identify technical debt including:
- Dead Code (unreachable code, unused variables, unused imports)
- Duplicate Logic (copy-pasted code that should be extracted)
- God Classes (classes with too many responsibilities, > 300 LOC)
- Long Methods (methods > 30 lines that should be split)
- Complex Methods (cyclomatic complexity > 10)
- Poor Naming (unclear variable/method names)
- Magic Numbers (hardcoded values without constants)
- Comment-out Code (commented code blocks left behind)
- TODO/FIXME comments indicating unfinished work
- Overly complex conditional logic

Calculate a Technical Debt Score (0-100, higher = less debt).

Return valid JSON:
{
  "debt_score": <0-100>,
  "estimated_remediation_hours": <integer>,
  "summary": "<debt assessment>",
  "findings": [
    {
      "category": "<dead_code|duplicate_logic|god_class|long_method|poor_naming|magic_number>",
      "title": "<issue title>",
      "severity": "<high|medium|low|info>",
      "file_path": "<path>",
      "line_start": <line or null>,
      "description": "<what the debt is>",
      "recommendation": "<how to reduce it>",
      "effort": "<small|medium|large>",
      "code_snippet": "<the problematic code>"
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

        return "Analyze this codebase for technical debt:\n\n{$fileContent}\n\nReturn findings as JSON.";
    }

    public function analyze(array $context): array
    {
        return $this->run($context);
    }

    protected function parseResponse(string $response): array
    {
        $data = $this->extractJson($response);

        return [
            'agent'                        => $this->getName(),
            'debt_score'                   => $data['debt_score'] ?? null,
            'estimated_remediation_hours'  => $data['estimated_remediation_hours'] ?? null,
            'summary'                      => $data['summary'] ?? '',
            'findings'                     => $data['findings'] ?? [],
        ];
    }
}
