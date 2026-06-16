<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

class ReportingAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'reporting';
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a Senior Engineering Manager preparing an executive technical report for stakeholders.

Your task is to synthesize findings from multiple specialized analysis agents and produce:
1. A clear executive summary non-technical stakeholders can understand
2. A prioritized action plan for the engineering team
3. Business impact assessment
4. Estimated engineering hours saved by addressing the issues

The report should be:
- Clear and professional
- Prioritize critical security and performance issues
- Quantify impact where possible
- Suggest a realistic remediation timeline

Return valid JSON:
{
  "executive_summary": "<2-3 paragraphs for non-technical stakeholders>",
  "key_findings": ["<top finding 1>", "<top finding 2>", "<top finding 3>"],
  "immediate_actions": ["<action 1>", "<action 2>"],
  "short_term_actions": ["<action 1>", "<action 2>"],
  "business_impact": "<how these issues affect the business>",
  "engineering_hours_saved": <estimated hours saved per month by fixing issues>,
  "remediation_timeline": {
    "week_1": "<what to fix in week 1>",
    "month_1": "<what to fix in month 1>",
    "quarter_1": "<what to fix in Q1>"
  },
  "risk_level": "<critical|high|medium|low>",
  "recommendation": "<overall recommendation>"
}
PROMPT;
    }

    protected function buildUserPrompt(array $context): string
    {
        $agentResults = $context['agent_results'] ?? [];
        $projectType  = $context['project_type'] ?? 'laravel';

        $summary = "ANALYSIS RESULTS FROM ALL AGENTS:\n\n";

        foreach ($agentResults as $agentName => $result) {
            $summary .= "=== {$agentName} ===\n";
            $summary .= "Score: " . ($result["{$agentName}_score"] ?? 'N/A') . "\n";
            $summary .= "Summary: " . ($result['summary'] ?? 'N/A') . "\n";
            $findingsCount = count($result['findings'] ?? []);
            $summary .= "Findings: {$findingsCount}\n\n";

            foreach (array_slice($result['findings'] ?? [], 0, 5) as $finding) {
                $summary .= "  [{$finding['severity']}] {$finding['title']}\n";
            }

            $summary .= "\n";
        }

        return <<<PROMPT
Project Type: {$projectType}

{$summary}

Generate an executive report synthesizing these findings. Return as JSON.
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
            'agent'                    => $this->getName(),
            'executive_summary'        => $data['executive_summary'] ?? '',
            'key_findings'             => $data['key_findings'] ?? [],
            'immediate_actions'        => $data['immediate_actions'] ?? [],
            'short_term_actions'       => $data['short_term_actions'] ?? [],
            'business_impact'          => $data['business_impact'] ?? '',
            'engineering_hours_saved'  => $data['engineering_hours_saved'] ?? 0,
            'remediation_timeline'     => $data['remediation_timeline'] ?? [],
            'risk_level'               => $data['risk_level'] ?? 'medium',
            'recommendation'           => $data['recommendation'] ?? '',
        ];
    }
}
