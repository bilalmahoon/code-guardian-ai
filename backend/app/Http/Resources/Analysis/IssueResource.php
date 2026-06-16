<?php

declare(strict_types=1);

namespace App\Http\Resources\Analysis;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'analysis_id'      => $this->analysis_id,
            'agent_type'       => $this->agent_type,
            'category'         => $this->category,
            'title'            => $this->title,
            'description'      => $this->description,
            'severity'         => $this->severity instanceof \App\Support\Enums\IssueSeverity
                ? $this->severity->value
                : $this->severity,
            'file_path'        => $this->file_path,
            'line_start'       => $this->line_start,
            'line_end'         => $this->line_end,
            'code_snippet'     => $this->code_snippet,
            'rule_id'          => $this->rule_id,
            'is_false_positive' => $this->is_false_positive,
            'dismissed_at'     => $this->dismissed_at?->toISOString(),
            'recommendation'   => $this->whenLoaded('recommendation', fn() =>
                new RecommendationResource($this->recommendation)
            ),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
