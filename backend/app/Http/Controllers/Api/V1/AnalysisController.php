<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Analysis\Jobs\RunAnalysisJob;
use App\Domain\Analysis\Models\Analysis;
use App\Domain\Organization\Models\Organization;
use App\Domain\Repository\Models\Project;
use App\Http\Controllers\Controller;
use App\Http\Resources\Analysis\AnalysisResource;
use App\Http\Resources\Analysis\IssueResource;
use App\Http\Resources\Analysis\RecommendationResource;
use App\Http\Resources\Analysis\GeneratedTestResource;
use App\Support\Enums\AnalysisStatus;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    use ApiResponse;

    public function index(Request $request, Organization $organization): JsonResponse
    {
        $analyses = Analysis::where('organization_id', $organization->id)
            ->with(['project', 'triggeredBy'])
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return $this->paginated($analyses);
    }

    public function store(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'project_id'  => ['required', 'uuid', 'exists:projects,id'],
            'source_ref'  => ['nullable', 'string', 'max:255'],
            'source_type' => ['sometimes', 'in:repo,pr,zip'],
        ]);

        $project = Project::where('id', $request->project_id)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        // Check subscription limit
        $subscription = $organization->subscription;
        if (! $subscription?->hasAnalysesRemaining()) {
            return $this->error('Analysis limit reached for your subscription plan', 402);
        }

        $analysis = Analysis::create([
            'organization_id' => $organization->id,
            'project_id'      => $project->id,
            'triggered_by'    => $request->user()->id,
            'trigger_type'    => 'manual',
            'source_type'     => $request->input('source_type', 'repo'),
            'source_ref'      => $request->input('source_ref', $project->default_branch),
            'status'          => AnalysisStatus::Pending,
        ]);

        $subscription->incrementAnalysisCount();

        RunAnalysisJob::dispatch($analysis->id);

        return $this->success([
            'analysis' => new AnalysisResource($analysis),
        ], 'Analysis queued successfully', 202);
    }

    public function show(Organization $organization, Analysis $analysis): JsonResponse
    {
        $this->authorize('view', [$analysis, $organization]);

        return $this->success([
            'analysis' => new AnalysisResource($analysis->load(['project', 'triggeredBy'])),
        ]);
    }

    public function cancel(Organization $organization, Analysis $analysis): JsonResponse
    {
        $this->authorize('update', [$analysis, $organization]);

        if ($analysis->status->isTerminal()) {
            return $this->error('Cannot cancel a completed or failed analysis', 422);
        }

        $analysis->update(['status' => AnalysisStatus::Cancelled]);

        return $this->success(message: 'Analysis cancelled');
    }

    public function issues(Request $request, Organization $organization, Analysis $analysis): JsonResponse
    {
        $issues = $analysis->issues()
            ->when($request->severity, fn($q) => $q->where('severity', $request->severity))
            ->when($request->agent_type, fn($q) => $q->where('agent_type', $request->agent_type))
            ->with('recommendation')
            ->paginate(50);

        $issues->getCollection()->transform(fn($i) => new IssueResource($i));

        return $this->paginated($issues);
    }

    public function recommendations(Request $request, Organization $organization, Analysis $analysis): JsonResponse
    {
        $recommendations = $analysis->recommendations()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->paginate(50);

        $recommendations->getCollection()->transform(fn($r) => new RecommendationResource($r));

        return $this->paginated($recommendations);
    }

    public function generatedTests(Organization $organization, Analysis $analysis): JsonResponse
    {
        $tests = $analysis->generatedTests()->paginate(50);

        $tests->getCollection()->transform(fn($t) => new GeneratedTestResource($t));

        return $this->paginated($tests);
    }

    public function report(Organization $organization, Analysis $analysis): JsonResponse
    {
        $report = $analysis->report;

        if (! $report) {
            return $this->error('Report not generated yet', 404);
        }

        return $this->success(['report' => $report]);
    }

    public function generateReport(Organization $organization, Analysis $analysis): JsonResponse
    {
        if (! $analysis->isCompleted()) {
            return $this->error('Analysis must be completed before generating a report', 422);
        }

        // TODO: Dispatch report generation job
        // GenerateReportJob::dispatch($analysis->id);

        return $this->success(message: 'Report generation queued', code: 202);
    }

    public function dismissIssue(Request $request, Organization $organization, Analysis $analysis, string $issueId): JsonResponse
    {
        $issue = $analysis->issues()->findOrFail($issueId);

        $issue->update([
            'is_false_positive' => true,
            'dismissed_at'      => now(),
            'dismissed_by'      => $request->user()->id,
        ]);

        return $this->success(message: 'Issue dismissed');
    }
}
