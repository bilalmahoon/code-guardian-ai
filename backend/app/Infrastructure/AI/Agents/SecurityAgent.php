<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

class SecurityAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'security';
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a Senior Application Security Engineer specializing in Laravel and Flutter security audits. 
You have deep knowledge of the OWASP Top 10 and common vulnerabilities in PHP web applications and Flutter mobile apps.

For Laravel, check for:
- SQL Injection (raw queries, user input in queries)
- XSS vulnerabilities (unescaped output, missing sanitization)
- CSRF protection gaps (missing middleware, token validation)
- Missing Authorization (endpoints without policy checks, missing Gates)
- Broken Authentication (weak tokens, missing verification)
- Secret/Key Exposure (hardcoded credentials, API keys in code)
- Mass Assignment vulnerabilities (missing $fillable/$guarded)
- Insecure Direct Object References
- Missing input validation
- Sensitive data exposure in logs

For Flutter/Dart, check for:
- Hardcoded API keys or secrets
- Insecure local storage (storing sensitive data in SharedPreferences)
- Missing certificate pinning
- Cleartext HTTP usage
- Insecure data transmission

Assign risk scores using CVSS-like severity:
- critical: CVSS 9.0-10.0 (immediate action required)
- high: CVSS 7.0-8.9 (fix in current sprint)
- medium: CVSS 4.0-6.9 (fix in next sprint)
- low: CVSS 2.0-3.9 (fix when convenient)
- info: CVSS 0.0-1.9 (informational)

Return valid JSON:
{
  "security_score": <0-100, higher is MORE secure>,
  "summary": "<security posture summary>",
  "critical_count": <number>,
  "high_count": <number>,
  "findings": [
    {
      "category": "<e.g. sql_injection, xss, missing_auth, hardcoded_key>",
      "title": "<issue title>",
      "severity": "<critical|high|medium|low|info>",
      "file_path": "<relative path>",
      "line_start": <line or null>,
      "description": "<detailed description of vulnerability>",
      "impact": "<what an attacker could do>",
      "recommendation": "<how to fix>",
      "code_before": "<vulnerable code>",
      "code_after": "<secure code>",
      "owasp_category": "<e.g. A01:2021>"
    }
  ]
}
PROMPT;
    }

    protected function buildUserPrompt(array $context): string
    {
        $files       = $context['files'] ?? [];
        $fileContent = '';

        // Prioritize security-sensitive files
        $priority = ['Controllers', 'Middleware', 'Policies', 'Routes', 'Models', 'Requests'];

        $sorted = [];
        foreach ($priority as $dir) {
            foreach ($files as $path => $content) {
                if (str_contains($path, $dir)) {
                    $sorted[$path] = $content;
                }
            }
        }

        foreach ($files as $path => $content) {
            if (! isset($sorted[$path])) {
                $sorted[$path] = $content;
            }
        }

        foreach ($sorted as $path => $content) {
            $fileContent .= "\n\n=== FILE: {$path} ===\n{$content}";
        }

        $fileContent = $this->truncateContext($fileContent, 60000);

        return <<<PROMPT
Perform a comprehensive security audit of the following codebase.

Project Type: {$context['project_type']}
Analysis ID: {$context['analysis_id']}

FILES TO AUDIT:
{$fileContent}

Identify all security vulnerabilities and return your findings as JSON.
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
            'agent'          => $this->getName(),
            'security_score' => $data['security_score'] ?? null,
            'summary'        => $data['summary'] ?? '',
            'critical_count' => $data['critical_count'] ?? 0,
            'high_count'     => $data['high_count'] ?? 0,
            'findings'       => $data['findings'] ?? [],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'temperature' => 0.05, // Very low temperature for security analysis
            'max_tokens'  => 4096,
        ];
    }
}
