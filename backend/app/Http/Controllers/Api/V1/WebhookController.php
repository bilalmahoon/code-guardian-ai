<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Analysis\Jobs\RunAnalysisJob;
use App\Domain\Analysis\Models\Analysis;
use App\Domain\Repository\Models\Repository;
use App\Http\Controllers\Controller;
use App\Support\Enums\AnalysisStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle an incoming webhook from a repository provider.
     */
    public function handle(Request $request, string $provider, string $repositoryId): JsonResponse
    {
        $repository = Repository::findOrFail($repositoryId);

        Log::info("Webhook received from {$provider}", [
            'repository_id' => $repositoryId,
            'event'         => $request->header('X-Event-Type') ?? $request->header('X-GitHub-Event'),
        ]);

        $event = $this->parseEvent($request, $provider);

        if ($event === null) {
            return response()->json(['status' => 'ignored']);
        }

        // Auto-trigger analysis on push to default branch or new PR
        $project = $repository->projects()->first();

        if ($project && $this->shouldTriggerAnalysis($event, $repository)) {
            $analysis = Analysis::create([
                'organization_id' => $repository->organization_id,
                'project_id'      => $project->id,
                'trigger_type'    => 'webhook',
                'source_type'     => $event['type'],
                'source_ref'      => $event['ref'] ?? $project->default_branch,
                'status'          => AnalysisStatus::Pending,
            ]);

            RunAnalysisJob::dispatch($analysis->id);
        }

        return response()->json(['status' => 'processed']);
    }

    private function parseEvent(Request $request, string $provider): ?array
    {
        $payload = $request->all();

        return match($provider) {
            'github' => $this->parseGithubEvent(
                $request->header('X-GitHub-Event', ''),
                $payload
            ),
            'bitbucket' => $this->parseBitbucketEvent(
                $payload['event_key'] ?? '',
                $payload
            ),
            'gitlab' => $this->parseGitlabEvent(
                $request->header('X-Gitlab-Event', ''),
                $payload
            ),
            default => null,
        };
    }

    private function parseGithubEvent(string $event, array $payload): ?array
    {
        return match($event) {
            'push' => [
                'type' => 'push',
                'ref'  => str_replace('refs/heads/', '', $payload['ref'] ?? ''),
            ],
            'pull_request' => [
                'type'   => 'pr',
                'ref'    => $payload['pull_request']['head']['ref'] ?? null,
                'pr_id'  => (string) ($payload['number'] ?? ''),
            ],
            default => null,
        };
    }

    private function parseBitbucketEvent(string $event, array $payload): ?array
    {
        if (str_starts_with($event, 'repo:push')) {
            return [
                'type' => 'push',
                'ref'  => $payload['push']['changes'][0]['new']['name'] ?? null,
            ];
        }

        if (str_starts_with($event, 'pullrequest:')) {
            return [
                'type'  => 'pr',
                'pr_id' => (string) ($payload['pullrequest']['id'] ?? ''),
            ];
        }

        return null;
    }

    private function parseGitlabEvent(string $event, array $payload): ?array
    {
        if ($event === 'Push Hook') {
            return [
                'type' => 'push',
                'ref'  => str_replace('refs/heads/', '', $payload['ref'] ?? ''),
            ];
        }

        if ($event === 'Merge Request Hook') {
            return [
                'type'  => 'pr',
                'pr_id' => (string) ($payload['object_attributes']['iid'] ?? ''),
            ];
        }

        return null;
    }

    private function shouldTriggerAnalysis(array $event, Repository $repository): bool
    {
        if ($event['type'] === 'pr') {
            return true;
        }

        if ($event['type'] === 'push') {
            return ($event['ref'] ?? '') === $repository->default_branch;
        }

        return false;
    }
}
