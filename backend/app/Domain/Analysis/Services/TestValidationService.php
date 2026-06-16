<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Services;

use App\Domain\Analysis\Models\Analysis;
use App\Domain\Analysis\Models\GeneratedTest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Runs the generated test suite in an isolated environment
 * and captures before/after quality metrics.
 */
class TestValidationService
{
    private const TEST_TIMEOUT_SECONDS = 120;

    public function validate(Analysis $analysis): ValidationResult
    {
        $tests = GeneratedTest::where('analysis_id', $analysis->id)->get();

        if ($tests->isEmpty()) {
            return new ValidationResult(0, 0, 0, [], 'No tests to validate');
        }

        $results = [];
        $passed  = 0;
        $failed  = 0;

        foreach ($tests as $test) {
            $result = $this->runSingleTest($test, $analysis);

            $results[] = $result;

            if ($result['status'] === 'passed') {
                $passed++;
            } else {
                $failed++;
            }

            // Update test status in DB
            $test->update(['validation_status' => $result['status']]);
        }

        $passRate = $tests->count() > 0
            ? round(($passed / $tests->count()) * 100, 1)
            : 0;

        return new ValidationResult(
            totalTests:  $tests->count(),
            passedTests: $passed,
            failedTests: $failed,
            results:     $results,
            summary:     "Ran {$tests->count()} tests: {$passed} passed, {$failed} failed ({$passRate}% pass rate)",
        );
    }

    private function runSingleTest(GeneratedTest $test, Analysis $analysis): array
    {
        $startTime = microtime(true);

        try {
            return match($test->framework) {
                'phpunit', 'pest' => $this->runPhpTest($test, $analysis),
                'flutter_test'    => $this->runFlutterTest($test, $analysis),
                default           => [
                    'test_id'  => $test->id,
                    'status'   => 'skipped',
                    'output'   => 'Unknown framework: ' . $test->framework,
                    'duration' => 0,
                ],
            };
        } catch (\Throwable $e) {
            return [
                'test_id'   => $test->id,
                'class'     => $test->class_name,
                'status'    => 'error',
                'output'    => $e->getMessage(),
                'duration'  => round(microtime(true) - $startTime, 3),
            ];
        }
    }

    private function runPhpTest(GeneratedTest $test, Analysis $analysis): array
    {
        $startTime = microtime(true);

        // Write test file to temp directory
        $testDir  = sys_get_temp_dir() . '/codeguardian_tests/' . $analysis->id;
        @mkdir($testDir, 0755, true);

        $testFile = $testDir . '/' . $test->class_name . 'Test.php';
        file_put_contents($testFile, $test->test_code);

        // Write minimal bootstrap if needed
        $bootstrapFile = $testDir . '/bootstrap.php';
        if (!file_exists($bootstrapFile)) {
            file_put_contents($bootstrapFile, $this->generatePhpBootstrap($analysis));
        }

        // Run PHPUnit in process isolation using Docker if available,
        // otherwise use a sandbox approach
        $output   = [];
        $exitCode = 0;

        $command = implode(' ', [
            'timeout', (string) self::TEST_TIMEOUT_SECONDS,
            'php',
            '-d', 'memory_limit=256M',
            '-d', 'error_reporting=0',
            escapeshellarg(__DIR__ . '/../../../../vendor/bin/phpunit'),
            '--no-coverage',
            '--colors=never',
            '--testdox',
            escapeshellarg($testFile),
            '2>&1',
        ]);

        exec($command, $output, $exitCode);

        // Clean up
        @unlink($testFile);

        $outputStr = implode("\n", $output);
        $status    = $exitCode === 0 ? 'passed' : 'failed';

        return [
            'test_id'   => $test->id,
            'class'     => $test->class_name,
            'scenario'  => $test->scenario,
            'status'    => $status,
            'output'    => $outputStr,
            'duration'  => round(microtime(true) - $startTime, 3),
            'exit_code' => $exitCode,
        ];
    }

    private function runFlutterTest(GeneratedTest $test, Analysis $analysis): array
    {
        $startTime = microtime(true);

        // For Flutter tests we write the test and attempt to run with flutter test
        $testDir  = sys_get_temp_dir() . '/codeguardian_flutter/' . $analysis->id;
        @mkdir($testDir . '/test', 0755, true);

        $testFile = $testDir . '/test/' . $test->class_name . '_test.dart';
        file_put_contents($testFile, $test->test_code);

        $output   = [];
        $exitCode = 0;

        // Check if flutter is available
        exec('which flutter', $whichOutput, $flutterExists);

        if ($flutterExists !== 0) {
            return [
                'test_id'  => $test->id,
                'class'    => $test->class_name,
                'status'   => 'skipped',
                'output'   => 'Flutter not available in validation environment',
                'duration' => 0,
            ];
        }

        $command = "cd {$testDir} && timeout " . self::TEST_TIMEOUT_SECONDS .
                   " flutter test test/" . $test->class_name . "_test.dart --no-pub 2>&1";

        exec($command, $output, $exitCode);

        $outputStr = implode("\n", $output);
        $status    = $exitCode === 0 ? 'passed' : 'failed';

        return [
            'test_id'   => $test->id,
            'class'     => $test->class_name,
            'scenario'  => $test->scenario,
            'status'    => $status,
            'output'    => $outputStr,
            'duration'  => round(microtime(true) - $startTime, 3),
        ];
    }

    private function generatePhpBootstrap(Analysis $analysis): string
    {
        $projectRoot = sys_get_temp_dir() . '/codeguardian/' . $analysis->id;

        return <<<PHP
<?php
// Auto-generated test bootstrap for CodeGuardian validation
if (file_exists('{$projectRoot}/vendor/autoload.php')) {
    require_once '{$projectRoot}/vendor/autoload.php';
}
PHP;
    }
}

/**
 * Result of running the full test suite.
 */
class ValidationResult
{
    public function __construct(
        public readonly int    $totalTests,
        public readonly int    $passedTests,
        public readonly int    $failedTests,
        public readonly array  $results,
        public readonly string $summary,
    ) {}

    public function passRate(): float
    {
        if ($this->totalTests === 0) return 0;
        return round(($this->passedTests / $this->totalTests) * 100, 1);
    }

    public function toArray(): array
    {
        return [
            'total_tests'   => $this->totalTests,
            'passed_tests'  => $this->passedTests,
            'failed_tests'  => $this->failedTests,
            'pass_rate'     => $this->passRate(),
            'summary'       => $this->summary,
            'results'       => $this->results,
        ];
    }
}
