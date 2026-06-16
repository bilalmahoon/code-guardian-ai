<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Services;

use App\Domain\Analysis\Models\Analysis;
use Illuminate\Support\Facades\DB;

class TestStorageService
{
    public function store(Analysis $analysis, array $qaResult): void
    {
        if (empty($qaResult['tests'])) {
            return;
        }

        $rows = [];
        $now  = now()->toDateTimeString();

        foreach ($qaResult['tests'] as $test) {
            $rows[] = [
                'id'          => \Illuminate\Support\Str::uuid()->toString(),
                'analysis_id' => $analysis->id,
                'type'        => $test['type'] ?? 'unit',
                'framework'   => $test['framework'] ?? 'phpunit',
                'class_name'  => $test['class_name'] ?? 'GeneratedTest',
                'test_code'   => $test['test_code'] ?? '',
                'scenario'    => $test['scenario'] ?? null,
                'coverage_area' => $test['coverage_area'] ?? null,
                'created_at'  => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('generated_tests')->insert($chunk);
        }
    }
}
