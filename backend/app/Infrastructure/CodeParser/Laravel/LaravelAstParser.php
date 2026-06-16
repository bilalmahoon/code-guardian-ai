<?php

declare(strict_types=1);

namespace App\Infrastructure\CodeParser\Laravel;

use App\Infrastructure\CodeParser\Contracts\ClassInfo;
use App\Infrastructure\CodeParser\Contracts\CodeMap;
use App\Infrastructure\CodeParser\Contracts\CodeParserContract;
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use Illuminate\Support\Facades\Log;

class LaravelAstParser implements CodeParserContract
{
    private \PhpParser\Parser $parser;
    private NodeFinder $finder;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->finder = new NodeFinder();
    }

    public function parse(array $files): CodeMap
    {
        $classes         = [];
        $dependencyGraph = [];
        $callGraph       = [];
        $fileMetrics     = [];

        foreach ($files as $filePath => $content) {
            if (!str_ends_with($filePath, '.php')) {
                continue;
            }

            try {
                $ast = $this->parser->parse($content);
                if ($ast === null) continue;

                $loc = substr_count($content, "\n") + 1;
                $fileMetrics[$filePath] = $loc;

                // Extract classes from AST
                $classNodes = $this->finder->findInstanceOf($ast, Stmt\ClassLike::class);

                foreach ($classNodes as $classNode) {
                    $classInfo = $this->extractClassInfo($classNode, $filePath, $content);
                    if ($classInfo) {
                        $classes[]                                    = $classInfo;
                        $dependencyGraph[$classInfo->name]            = $classInfo->dependencies;
                        $callGraph[$classInfo->name]                  = $this->buildCallGraph($classNode);
                    }
                }

            } catch (Error $e) {
                Log::warning("AST parse error in {$filePath}: " . $e->getMessage());
            }
        }

        return new CodeMap(
            classes:          $classes,
            routes:           $this->extractRoutes($files),
            dependencyGraph:  $dependencyGraph,
            callGraph:        $callGraph,
            fileMetrics:      $fileMetrics,
        );
    }

    private function extractClassInfo(Stmt\ClassLike $node, string $filePath, string $content): ?ClassInfo
    {
        if ($node->name === null) {
            return null; // Anonymous class
        }

        $namespace = $this->resolveNamespace($node);
        $type      = match(true) {
            $node instanceof Stmt\Interface_ => 'interface',
            $node instanceof Stmt\Trait_     => 'trait',
            $node instanceof Stmt\Enum_      => 'enum',
            $node instanceof Stmt\Class_ && $node->isAbstract() => 'abstract',
            default => 'class',
        };

        $extends    = null;
        $implements = [];

        if ($node instanceof Stmt\Class_) {
            $extends    = $node->extends?->toString();
            $implements = array_map(fn($i) => $i->toString(), $node->implements);
        }

        $uses = [];
        foreach ($this->finder->findInstanceOf([$node], Stmt\TraitUse::class) as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $uses[] = $trait->toString();
            }
        }

        $methods      = $this->extractMethods($node);
        $properties   = $this->extractProperties($node);
        $dependencies = $this->extractConstructorDependencies($node);
        $complexity   = $this->calculateComplexity($node);
        $loc          = ($node->getEndLine() - $node->getStartLine()) + 1;

        return new ClassInfo(
            name:                 $node->name->toString(),
            type:                 $type,
            namespace:            $namespace,
            filePath:             $filePath,
            lineStart:            $node->getStartLine(),
            lineEnd:              $node->getEndLine(),
            extends:              $extends,
            implements:           $implements,
            uses:                 $uses,
            methods:              $methods,
            properties:           $properties,
            dependencies:         $dependencies,
            loc:                  $loc,
            cyclomaticComplexity: $complexity,
        );
    }

    private function extractMethods(Stmt\ClassLike $node): array
    {
        $methods = [];

        foreach ($this->finder->findInstanceOf([$node], Stmt\ClassMethod::class) as $method) {
            $params = [];
            foreach ($method->params as $param) {
                $params[] = [
                    'name' => '$' . $param->var->name,
                    'type' => $param->type?->toString() ?? 'mixed',
                ];
            }

            $methods[] = [
                'name'        => $method->name->toString(),
                'visibility'  => $this->getVisibility($method),
                'is_static'   => $method->isStatic(),
                'is_abstract' => $method->isAbstract(),
                'params'      => $params,
                'return_type' => $method->returnType?->toString() ?? null,
                'line_start'  => $method->getStartLine(),
                'line_end'    => $method->getEndLine(),
                'loc'         => ($method->getEndLine() - $method->getStartLine()) + 1,
            ];
        }

        return $methods;
    }

    private function extractProperties(Stmt\ClassLike $node): array
    {
        $properties = [];

        foreach ($this->finder->findInstanceOf([$node], Stmt\Property::class) as $prop) {
            foreach ($prop->props as $p) {
                $properties[] = [
                    'name'       => '$' . $p->name->toString(),
                    'visibility' => $this->getVisibility($prop),
                    'type'       => $prop->type?->toString() ?? null,
                    'is_static'  => $prop->isStatic(),
                ];
            }
        }

        return $properties;
    }

    private function extractConstructorDependencies(Stmt\ClassLike $node): array
    {
        $dependencies = [];

        foreach ($this->finder->findInstanceOf([$node], Stmt\ClassMethod::class) as $method) {
            if ($method->name->toString() !== '__construct') continue;

            foreach ($method->params as $param) {
                $type = $param->type?->toString();
                if ($type && !in_array($type, ['string', 'int', 'bool', 'array', 'float', 'mixed', 'null'])) {
                    $dependencies[] = $type;
                }
            }
        }

        return $dependencies;
    }

    private function calculateComplexity(Stmt\ClassLike $node): int
    {
        $complexity = 0;

        $decisionNodes = $this->finder->find([$node], fn(Node $n) =>
            $n instanceof Node\Stmt\If_ ||
            $n instanceof Node\Stmt\ElseIf_ ||
            $n instanceof Node\Stmt\For_ ||
            $n instanceof Node\Stmt\Foreach_ ||
            $n instanceof Node\Stmt\While_ ||
            $n instanceof Node\Stmt\Do_ ||
            $n instanceof Node\Stmt\Switch_ ||
            $n instanceof Node\Stmt\Catch_ ||
            $n instanceof Node\Expr\Match_ ||
            $n instanceof Node\Expr\BinaryOp\BooleanAnd ||
            $n instanceof Node\Expr\BinaryOp\BooleanOr ||
            $n instanceof Node\Expr\Ternary
        );

        return count($decisionNodes);
    }

    private function buildCallGraph(Stmt\ClassLike $node): array
    {
        $calls = [];

        $methodCalls = $this->finder->findInstanceOf([$node], Node\Expr\MethodCall::class);
        $staticCalls = $this->finder->findInstanceOf([$node], Node\Expr\StaticCall::class);

        foreach ($methodCalls as $call) {
            if ($call->name instanceof Node\Identifier) {
                $calls[] = $call->name->toString();
            }
        }

        foreach ($staticCalls as $call) {
            if ($call->class instanceof Node\Name && $call->name instanceof Node\Identifier) {
                $calls[] = $call->class->toString() . '::' . $call->name->toString();
            }
        }

        return array_unique($calls);
    }

    private function extractRoutes(array $files): array
    {
        $routes = [];

        foreach ($files as $filePath => $content) {
            if (!str_contains($filePath, 'routes/')) continue;

            preg_match_all(
                '/Route::(get|post|put|patch|delete|any)\s*\(\s*[\'"]([^\'"]+)[\'"]/',
                $content,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $routes[] = strtoupper($match[1]) . ' ' . $match[2];
            }
        }

        return array_unique($routes);
    }

    private function resolveNamespace(Stmt\ClassLike $node): string
    {
        return $node->namespacedName?->toString() ?? $node->name?->toString() ?? '';
    }

    private function getVisibility(Stmt\ClassMethod|Stmt\Property $node): string
    {
        return match(true) {
            $node->isPublic()    => 'public',
            $node->isProtected() => 'protected',
            $node->isPrivate()   => 'private',
            default              => 'public',
        };
    }
}
