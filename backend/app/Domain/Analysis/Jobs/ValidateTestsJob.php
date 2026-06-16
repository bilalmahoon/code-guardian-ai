<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Jobs;

use App\Domain\Analysis\Models\Analysis;
use App\Domain\Analysis\Services\TestValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ValidateTestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(public readonly string $analysisId)
    {
        $this->onQueue('validation');
    }

    public function handle(TestValidationService $validationService): void
    {
        $analysis = Analysis::findOrFail($this->analysisId);

        Log::info("Starting test validation for analysis {$this->analysisId}");

        $result = $validationService->validate($analysis);

        // Store validation summary on the analysis
        $agentStatuses = $analysis->agent_statuses ?? [];
        $agentStatuses['test_validation'] = [
            'status'     => 'completed',
            'pass_rate'  => $result->passRate(),
            'total'      => $result->totalTests,
            'passed'     => $result->passedTests,
            'failed'     => $result->failedTests,
            'summary'    => $result->summary,
        ];

        $analysis->update(['agent_statuses' => $agentStatuses]);

        Log::info("Test validation complete: {$result->summary}");
    }
}
