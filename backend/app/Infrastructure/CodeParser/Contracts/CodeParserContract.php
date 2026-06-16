<?php

declare(strict_types=1);

namespace App\Infrastructure\CodeParser\Contracts;

interface CodeParserContract
{
    /**
     * Parse all files and return a structured code map.
     *
     * @param  array<string, string>  $files  ['relative/path.php' => 'file content']
     * @return CodeMap
     */
    public function parse(array $files): CodeMap;
}

/**
 * The full structured map of a parsed codebase.
 */
class CodeMap
{
    public function __construct(
        /** @var ClassInfo[] */
        public array $classes = [],
        /** @var string[] */
        public array $routes = [],
        /** @var array<string, string[]> Dependency graph: class → [dependencies] */
        public array $dependencyGraph = [],
        /** @var array<string, string[]> Call graph: method → [called methods] */
        public array $callGraph = [],
        /** @var array<string, int> File metrics: file → LOC */
        public array $fileMetrics = [],
    ) {}

    public function toArray(): array
    {
        return [
            'classes'          => array_map(fn($c) => $c->toArray(), $this->classes),
            'routes'           => $this->routes,
            'dependency_graph' => $this->dependencyGraph,
            'call_graph'       => $this->callGraph,
            'file_metrics'     => $this->fileMetrics,
            'summary'          => [
                'total_classes'   => count($this->classes),
                'total_methods'   => array_sum(array_map(fn($c) => count($c->methods), $this->classes)),
                'total_files'     => count($this->fileMetrics),
                'total_loc'       => array_sum($this->fileMetrics),
            ],
        ];
    }
}

class ClassInfo
{
    public function __construct(
        public string $name,
        public string $type,        // class, abstract, interface, trait
        public string $namespace,
        public string $filePath,
        public int $lineStart,
        public int $lineEnd,
        public ?string $extends = null,
        public array $implements = [],
        public array $uses = [],    // traits used
        public array $methods = [],
        public array $properties = [],
        public array $dependencies = [], // injected via constructor
        public int $loc = 0,
        public int $cyclomaticComplexity = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'name'                 => $this->name,
            'type'                 => $this->type,
            'namespace'            => $this->namespace,
            'file_path'            => $this->filePath,
            'line_start'           => $this->lineStart,
            'line_end'             => $this->lineEnd,
            'extends'              => $this->extends,
            'implements'           => $this->implements,
            'uses'                 => $this->uses,
            'methods'              => $this->methods,
            'properties'           => $this->properties,
            'dependencies'         => $this->dependencies,
            'loc'                  => $this->loc,
            'cyclomatic_complexity' => $this->cyclomaticComplexity,
        ];
    }
}
