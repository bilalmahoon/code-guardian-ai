<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Services;

use App\Domain\Analysis\Models\Analysis;
use Illuminate\Support\Facades\DB;

class IssueStorageService
{
    public function store(Analysis $analysis, array $agentResults): void
    {
        $issues          = [];
        $recommendations = [];
        $now             = now()->toDateTimeString();

        foreach ($agentResults as $agentName => $result) {
            foreach ($result['findings'] ?? [] as $finding) {
                $issueId = \Illuminate\Support\Str::uuid()->toString();

                $issues[] = [
                    'id'           => $issueId,
                    'analysis_id'  => $analysis->id,
                    'agent_type'   => $agentName,
                    'category'     => $finding['category'] ?? 'general',
                    'title'        => $finding['title'] ?? 'Issue',
                    'description'  => $finding['description'] ?? '',
                    'severity'     => $finding['severity'] ?? 'medium',
                    'file_path'    => $finding['file_path'] ?? null,
                    'line_start'   => $finding['line_start'] ?? null,
                    'line_end'     => $finding['line_end'] ?? null,
                    'code_snippet' => $finding['code_snippet'] ?? $finding['code_before'] ?? null,
                    'rule_id'      => $finding['owasp_category'] ?? null,
                    'created_at'   => $now,
                ];

                // Create recommendation if fix is provided
                if (! empty($finding['recommendation'])) {
                    $recommendations[] = [
                        'id'                   => \Illuminate\Support\Str::uuid()->toString(),
                        'issue_id'             => $issueId,
                        'analysis_id'          => $analysis->id,
                        'problem_description'  => $finding['description'] ?? '',
                        'root_cause'           => $finding['root_cause'] ?? $finding['impact'] ?? null,
                        'risk_level'           => $finding['severity'] ?? 'medium',
                        'recommended_fix'      => $finding['recommendation'],
                        'code_before'          => $finding['code_before'] ?? null,
                        'code_after'           => $finding['code_after'] ?? null,
                        'estimated_improvement' => $finding['estimated_improvement'] ?? null,
                        'created_at'           => $now,
                    ];
                }
            }
        }

        // Batch insert for performance
        foreach (array_chunk($issues, 100) as $chunk) {
            DB::table('issues')->insert($chunk);
        }

        foreach (array_chunk($recommendations, 100) as $chunk) {
            DB::table('recommendations')->insert($chunk);
        }
    }
}
