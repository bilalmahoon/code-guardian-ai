<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

class DevOpsAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'devops';
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a Senior DevOps Engineer specializing in Laravel application deployment and infrastructure.

Review for:
- Dockerfile best practices (multi-stage builds, non-root user, minimal image)
- Docker Compose configuration quality
- CI/CD pipeline completeness and quality
- Environment variable management (no secrets in code, .env.example completeness)
- Missing health checks
- Missing resource limits
- Security hardening (running as non-root, read-only filesystems)
- Missing backup configuration
- Log management configuration
- Deployment risk patterns (no rollback plan, big-bang deployments)

Return valid JSON:
{
  "devops_score": <0-100>,
  "deployment_ready": <true|false>,
  "summary": "<deployment readiness assessment>",
  "findings": [
    {
      "category": "<dockerfile|ci_cd|env_management|security|logging>",
      "title": "<issue>",
      "severity": "<high|medium|low|info>",
      "file_path": "<path>",
      "description": "<what the issue is>",
      "recommendation": "<how to fix>",
      "code_before": "<current config>",
      "code_after": "<improved config>"
    }
  ]
}
PROMPT;
    }

    protected function buildUserPrompt(array $context): string
    {
        $files       = $context['files'] ?? [];
        $devopsFiles = [];

        // Only include DevOps-relevant files
        $relevant = ['Dockerfile', 'docker-compose', '.yml', '.yaml', '.env', 'Makefile', 'deploy'];
        foreach ($files as $path => $content) {
            foreach ($relevant as $pattern) {
                if (str_contains(strtolower($path), strtolower($pattern))) {
                    $devopsFiles[$path] = $content;
                    break;
                }
            }
        }

        $fileContent = '';
        foreach ($devopsFiles as $path => $content) {
            $fileContent .= "\n\n=== FILE: {$path} ===\n{$content}";
        }

        if (empty($fileContent)) {
            $fileContent = "No DevOps configuration files found.";
        }

        return "Review the DevOps configuration:\n\n{$fileContent}\n\nReturn findings as JSON.";
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
            'devops_score'       => $data['devops_score'] ?? null,
            'deployment_ready'   => $data['deployment_ready'] ?? false,
            'summary'            => $data['summary'] ?? '',
            'findings'           => $data['findings'] ?? [],
        ];
    }
}
