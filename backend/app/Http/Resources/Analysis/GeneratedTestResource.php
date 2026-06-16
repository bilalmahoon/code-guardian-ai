<?php

declare(strict_types=1);

namespace App\Http\Resources\Analysis;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneratedTestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'analysis_id'      => $this->analysis_id,
            'issue_id'         => $this->issue_id,
            'type'             => $this->type,
            'framework'        => $this->framework,
            'class_name'       => $this->class_name,
            'test_code'        => $this->test_code,
            'scenario'         => $this->scenario,
            'coverage_area'    => $this->coverage_area,
            'validation_status' => $this->validation_status,
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
