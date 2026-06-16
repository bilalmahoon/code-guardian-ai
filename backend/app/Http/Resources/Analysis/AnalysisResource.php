<?php

declare(strict_types=1);

namespace App\Http\Resources\Analysis;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'organization_id'       => $this->organization_id,
            'project_id'            => $this->project_id,
            'triggered_by'          => $this->triggered_by,
            'trigger_type'          => $this->trigger_type,
            'source_type'           => $this->source_type,
            'source_ref'            => $this->source_ref,
            'status'                => $this->status,
            'scores'                => [
                'overall'          => $this->overall_score,
                'security'         => $this->security_score,
                'performance'      => $this->performance_score,
                'architecture'     => $this->architecture_score,
                'maintainability'  => $this->maintainability_score,
                'quality'          => $this->quality_score,
                'debt'             => $this->debt_score,
                'devops'           => $this->devops_score,
            ],
            'ai_provider_used'      => $this->ai_provider_used,
            'tokens_consumed'       => $this->tokens_consumed,
            'processing_seconds'    => $this->getDurationInSeconds(),
            'agent_statuses'        => $this->agent_statuses,
            'error_message'         => $this->error_message,
            'project'               => $this->whenLoaded('project', fn() => [
                'id'   => $this->project->id,
                'name' => $this->project->name,
                'type' => $this->project->type,
            ]),
            'started_at'            => $this->started_at?->toISOString(),
            'completed_at'          => $this->completed_at?->toISOString(),
            'created_at'            => $this->created_at->toISOString(),
        ];
    }
}
