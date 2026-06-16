<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Jobs;

use App\Domain\Analysis\Models\Analysis;
use App\Domain\Analysis\Services\AgentOrchestrator;
use App\Domain\Analysis\Services\CodeContextBuilder;
use App\Domain\Analysis\Services\IssueStorageService;
use App\Domain\Analysis\Services\TestStorageService;
use App\Domain\Analysis\Jobs\ValidateTestsJob;
use App\Support\Enums\AnalysisStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout     = 600; // 10 minutes
    public int $tries       = 1;
    public int $maxExceptions = 1;

    public function __construct(
        public readonly string $analysisId,
    ) {
        $this->onQueue('ai-analysis');
    }

    public function handle(
        AgentOrchestrator    $orchestrator,
        CodeContextBuilder   $contextBuilder,
        IssueStorageService  $issueStorage,
        TestStorageService   $testStorage,
    ): void {
        $analysis = Analysis::findOrFail($this->analysisId);

        if ($analysis->status !== AnalysisStatus::Pending) {
            Log::warning("Analysis {$this->analysisId} is not in pending state, skipping");
            return;
        }

        try {
            // Build code context from repository
            $codeContext = $contextBuilder->build($analysis);

            // Run the agent pipeline
            $results = $orchestrator->run($analysis, $codeContext);

            // Persist findings to database
            $issueStorage->store($analysis, $results['agent_results']);
            $testStorage->store($analysis, $results['agent_results']['qa'] ?? []);

            // Dispatch test validation as a chained job
            ValidateTestsJob::dispatch($analysis->id);

            Log::info("Analysis {$this->analysisId} completed", [
                'overall_score' => $results['scores']['overall_score'],
                'duration_s'    => $analysis->getDurationInSeconds(),
            ]);

        } catch (\Throwable $e) {
            Log::error("Analysis {$this->analysisId} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $analysis->update([
                'status'        => AnalysisStatus::Failed,
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $analysis = Analysis::find($this->analysisId);
        $analysis?->update([
            'status'        => AnalysisStatus::Failed,
            'error_message' => $exception->getMessage(),
            'completed_at'  => now(),
        ]);
    }
}
