<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Services;

use App\Domain\Analysis\Models\Analysis;
use App\Infrastructure\CodeParser\Contracts\CodeMap;
use App\Infrastructure\CodeParser\Laravel\LaravelAstParser;
use App\Infrastructure\RepositoryProviders\RepositoryProviderFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CodeContextBuilder
{
    private const LARAVEL_EXTENSIONS = ['php'];
    private const FLUTTER_EXTENSIONS = ['dart'];

    private const SKIP_DIRS = [
        'vendor', 'node_modules', '.git', 'storage', 'bootstrap/cache',
        '.dart_tool', 'build', '.pub-cache', 'test', 'tests',
    ];

    private const MAX_FILE_SIZE = 100_000;

    public function __construct(
        private readonly LaravelAstParser $phpParser,
    ) {}

    public function build(Analysis $analysis): array
    {
        $project    = $analysis->project;
        $repository = $project->repository;
        $projectType = $project->type;

        $extensions = $projectType === 'flutter'
            ? self::FLUTTER_EXTENSIONS
            : self::LARAVEL_EXTENSIONS;

        $files = $this->getFiles($analysis, $repository, $extensions);

        // For Laravel projects: run AST parsing and enrich the context
        $codeMap = null;
        if ($projectType === 'laravel' && !empty($files)) {
            $codeMap = $this->phpParser->parse($files);
        }

        return [
            'analysis_id'  => $analysis->id,
            'project_type' => $projectType,
            'project_name' => $project->name,
            'branch'       => $analysis->source_ref ?? $project->default_branch,
            'files'        => $files,
            'code_map'     => $codeMap?->toArray(),
            'summary'      => $this->buildSummary($files, $codeMap),
        ];
    }

    private function buildSummary(array $files, ?CodeMap $codeMap): array
    {
        $summary = [
            'total_files' => count($files),
            'total_loc'   => array_sum(array_map(
                fn($content) => substr_count($content, "\n") + 1,
                $files
            )),
        ];

        if ($codeMap) {
            $mapSummary            = $codeMap->toArray()['summary'];
            $summary['classes']    = $mapSummary['total_classes'];
            $summary['methods']    = $mapSummary['total_methods'];
            $summary['routes']     = count($codeMap->routes);
        }

        return $summary;
    }

    private function getFiles(Analysis $analysis, $repository, array $extensions): array
    {
        $snapshotPath = "snapshots/{$analysis->id}/";

        if (Storage::disk('s3')->exists($snapshotPath . 'manifest.json')) {
            return $this->loadFromSnapshot($snapshotPath);
        }

        return $this->cloneAndIndex($analysis, $repository, $extensions, $snapshotPath);
    }

    private function cloneAndIndex(Analysis $analysis, $repository, array $extensions, string $snapshotPath): array
    {
        $tempDir = sys_get_temp_dir() . '/codeguardian/' . $analysis->id;

        try {
            $provider    = RepositoryProviderFactory::make($repository->provider);
            $accessToken = decrypt($repository->access_token);

            $provider->cloneRepository($repository, $accessToken, $tempDir);

            $files = $this->indexDirectory($tempDir, $extensions);

            $this->cacheToStorage($files, $snapshotPath);

            return $files;
        } catch (\Throwable $e) {
            Log::error('Failed to clone repository for analysis', [
                'analysis_id'  => $analysis->id,
                'repository'   => $repository->full_name,
                'error'        => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            if (is_dir($tempDir)) {
                exec("rm -rf " . escapeshellarg($tempDir));
            }
        }
    }

    private function indexDirectory(string $dir, array $extensions): array
    {
        $files   = [];
        $baseLen = strlen($dir) + 1;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) continue;

            $relativePath = substr($file->getPathname(), $baseLen);

            foreach (self::SKIP_DIRS as $skip) {
                if (str_starts_with($relativePath, $skip . DIRECTORY_SEPARATOR)) {
                    continue 2;
                }
            }

            if (! in_array($file->getExtension(), $extensions)) continue;

            if ($file->getSize() > self::MAX_FILE_SIZE) {
                Log::debug("Skipping large file: {$relativePath} ({$file->getSize()} bytes)");
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content !== false) {
                $files[$relativePath] = $content;
            }
        }

        return $files;
    }

    private function loadFromSnapshot(string $snapshotPath): array
    {
        $manifest = json_decode(
            Storage::disk('s3')->get($snapshotPath . 'manifest.json'),
            true
        );

        $files = [];
        foreach ($manifest['files'] ?? [] as $relativePath) {
            $content = Storage::disk('s3')->get($snapshotPath . $relativePath);
            if ($content !== null) {
                $files[$relativePath] = $content;
            }
        }

        return $files;
    }

    private function cacheToStorage(array $files, string $snapshotPath): void
    {
        try {
            Storage::disk('s3')->put(
                $snapshotPath . 'manifest.json',
                json_encode(['files' => array_keys($files), 'cached_at' => now()->toISOString()])
            );

            foreach ($files as $path => $content) {
                Storage::disk('s3')->put($snapshotPath . $path, $content);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to cache code snapshot', ['error' => $e->getMessage()]);
        }
    }
}
