<?php

declare(strict_types=1);

namespace App\Http\Resources\Analysis;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'issue_id'             => $this->issue_id,
            'analysis_id'          => $this->analysis_id,
            'problem_description'  => $this->problem_description,
            'root_cause'           => $this->root_cause,
            'risk_level'           => $this->risk_level,
            'recommended_fix'      => $this->recommended_fix,
            'code_before'          => $this->code_before,
            'code_after'           => $this->code_after,
            'estimated_improvement' => $this->estimated_improvement,
            'status'               => $this->status,
            'applied_at'           => $this->applied_at?->toISOString(),
            'created_at'           => $this->created_at?->toISOString(),
        ];
    }
}
