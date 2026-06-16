<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Services;

use App\Domain\Analysis\Models\Analysis;
use App\Infrastructure\AI\Agents\DevOpsAgent;
use App\Infrastructure\AI\Agents\FlutterArchitectAgent;
use App\Infrastructure\AI\Agents\LaravelArchitectAgent;
use App\Infrastructure\AI\Agents\PerformanceAgent;
use App\Infrastructure\AI\Agents\QaAgent;
use App\Infrastructure\AI\Agents\ReportingAgent;
use App\Infrastructure\AI\Agents\SecurityAgent;
use App\Infrastructure\AI\Agents\TechDebtAgent;
use App\Support\Enums\AnalysisStatus;
use Illuminate\Support\Facades\Log;

class AgentOrchestrator
{
    public function __construct(
        private readonly LaravelArchitectAgent $laravelAgent,
        private readonly FlutterArchitectAgent $flutterAgent,
        private readonly SecurityAgent         $securityAgent,
        private readonly PerformanceAgent      $performanceAgent,
        private readonly QaAgent               $qaAgent,
        private readonly TechDebtAgent         $debtAgent,
        private readonly DevOpsAgent           $devopsAgent,
        private readonly ReportingAgent        $reportingAgent,
    ) {}

    public function run(Analysis $analysis, array $codeContext): array
    {
        $this->updateAnalysisStatus($analysis, AnalysisStatus::Processing);

        try {
            // Phase 1: Run architecture + security + performance + debt + devops in parallel
            // (In production these are dispatched as separate queue jobs; here we run sequentially
            //  because the orchestrator is called from RunAnalysisJob on the ai-analysis queue)
            $agentResults = $this->runPhase1($analysis, $codeContext);

            // Phase 2: QA Agent needs Phase 1 findings to generate targeted tests
            $allIssues   = $this->collectAllIssues($agentResults);
            $qaContext    = array_merge($codeContext, ['issues' => $allIssues]);
            $agentResults['qa'] = $this->runAgent($analysis, $this->qaAgent, $qaContext);

            // Phase 3: Reporting synthesises everything
            $reportContext = [
                'project_type'  => $codeContext['project_type'],
                'analysis_id'   => $codeContext['analysis_id'],
                'agent_results' => $agentResults,
            ];
            $agentResults['reporting'] = $this->runAgent($analysis, $this->reportingAgent, $reportContext);

            $scores = $this->calculateScores($agentResults, $codeContext['project_type']);

            $analysis->update([
                'status'                => AnalysisStatus::Completed,
                'architecture_score'    => $scores['architecture_score'],
                'security_score'        => $scores['security_score'],
                'performance_score'     => $scores['performance_score'],
                'maintainability_score' => $scores['maintainability_score'],
                'quality_score'         => $scores['quality_score'],
                'debt_score'            => $scores['debt_score'],
                'devops_score'          => $scores['devops_score'],
                'overall_score'         => $scores['overall_score'],
                'completed_at'          => now(),
            ]);

            return ['agent_results' => $agentResults, 'scores' => $scores];

        } catch (\Throwable $e) {
            Log::error('Analysis pipeline failed', [
                'analysis_id' => $analysis->id,
                'error'       => $e->getMessage(),
            ]);

            $analysis->update([
                'status'        => AnalysisStatus::Failed,
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            throw $e;
        }
    }

    private function runPhase1(Analysis $analysis, array $context): array
    {
        $projectType = $context['project_type'] ?? 'laravel';
        $results     = [];

        // Architecture agent depends on project type
        if ($projectType === 'flutter') {
            $results['flutter_architect'] = $this->runAgent($analysis, $this->flutterAgent, $context);
        } else {
            $results['laravel_architect'] = $this->runAgent($analysis, $this->laravelAgent, $context);
        }

        // These agents run on both project types
        $results['security']    = $this->runAgent($analysis, $this->securityAgent, $context);
        $results['performance'] = $this->runAgent($analysis, $this->performanceAgent, $context);
        $results['tech_debt']   = $this->runAgent($analysis, $this->debtAgent, $context);
        $results['devops']      = $this->runAgent($analysis, $this->devopsAgent, $context);

        return $results;
    }

    private function runAgent(Analysis $analysis, $agent, array $context): array
    {
        $agentName = $agent->getName();

        $statuses              = $analysis->agent_statuses ?? [];
        $statuses[$agentName]  = 'running';
        $analysis->update(['agent_statuses' => $statuses]);

        try {
            $result = $agent->analyze($context);

            $statuses[$agentName] = 'completed';
            $analysis->update(['agent_statuses' => $statuses]);

            return $result;
        } catch (\Throwable $e) {
            $statuses[$agentName] = 'failed';
            $analysis->update(['agent_statuses' => $statuses]);

            Log::warning("Agent {$agentName} failed", [
                'analysis_id' => $analysis->id,
                'error'       => $e->getMessage(),
            ]);

            return ['agent' => $agentName, 'error' => $e->getMessage(), 'findings' => []];
        }
    }

    private function collectAllIssues(array $agentResults): array
    {
        $issues = [];
        foreach ($agentResults as $result) {
            foreach ($result['findings'] ?? [] as $finding) {
                $issues[] = $finding;
            }
        }
        return $issues;
    }

    private function calculateScores(array $agentResults, string $projectType): array
    {
        // Architecture score — use correct agent key depending on project type
        $archKey      = $projectType === 'flutter' ? 'flutter_architect' : 'laravel_architect';
        $architecture = $agentResults[$archKey]['architecture_score'] ?? 70;
        $security     = $agentResults['security']['security_score'] ?? 70;
        $performance  = $agentResults['performance']['performance_score'] ?? 70;
        $debt         = $agentResults['tech_debt']['debt_score'] ?? 70;
        $devops       = $agentResults['devops']['devops_score'] ?? 70;

        $overall = (int) round(
            ($architecture * 0.25) +
            ($security     * 0.30) +
            ($performance  * 0.20) +
            ($debt         * 0.15) +
            ($devops       * 0.10)
        );

        return [
            'architecture_score'    => $architecture,
            'security_score'        => $security,
            'performance_score'     => $performance,
            'maintainability_score' => $debt,
            'quality_score'         => (int) round(($architecture + $performance + $debt) / 3),
            'debt_score'            => $debt,
            'devops_score'          => $devops,
            'overall_score'         => $overall,
        ];
    }

    private function updateAnalysisStatus(Analysis $analysis, AnalysisStatus $status): void
    {
        $update = ['status' => $status];
        if ($status === AnalysisStatus::Processing) {
            $update['started_at'] = now();
        }
        $analysis->update($update);
    }
}
