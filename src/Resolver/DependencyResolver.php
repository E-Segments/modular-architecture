<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Resolver;

use Esegments\ModularArchitecture\Exceptions\CircularDependencyException;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleCollection;
use MJS\TopSort\Implementations\StringSort;
use Sweetchuck\cdd\CircularDependencyDetector;

class DependencyResolver
{
    protected const MAX_CHAIN_DEPTH = 100;

    protected CircularDependencyDetector $circularDetector;

    public function __construct(?CircularDependencyDetector $circularDetector = null)
    {
        $this->circularDetector = $circularDetector ?? new CircularDependencyDetector;
    }

    /**
     * Resolve module load order based on dependencies.
     */
    public function resolve(ModuleCollection $modules): ModuleCollection
    {
        if ($modules->isEmpty()) {
            return $modules;
        }

        // Detect cycles first using sweetchuck/cdd
        $cycles = $this->detectCycles($modules);
        if (! empty($cycles)) {
            throw CircularDependencyException::forMultipleCycles($cycles);
        }

        // Build topological sort using marcj/topsort
        $sorter = new StringSort;

        foreach ($modules as $module) {
            $validDeps = $this->getValidDependencies($module, $modules);
            $sorter->add($module->getName(), $validDeps);
        }

        try {
            $sorted = $sorter->sort();
        } catch (\Exception $e) {
            // Fallback to priority-based sorting
            return $modules->sortByPriority();
        }

        // Build new collection in sorted order
        $ordered = new ModuleCollection;

        foreach ($sorted as $name) {
            $module = $modules->findByName($name);
            if ($module) {
                $ordered->put($name, $module);
            }
        }

        return $ordered;
    }

    /**
     * Detect circular dependencies using sweetchuck/cdd.
     *
     * @return array<array<string>> Array of cycles, each cycle is an array of module names
     */
    public function detectCycles(ModuleCollection $modules): array
    {
        $graph = $this->buildDependencyGraph($modules);

        if (empty($graph)) {
            return [];
        }

        // sweetchuck/cdd returns: ['a|b' => ['b', 'a', 'b']]
        $detected = $this->circularDetector->detect($graph);

        // Convert to our format: [['a', 'b', 'a'], ...]
        return array_values($detected);
    }

    /**
     * Build dependency graph from modules.
     *
     * @return array<string, array<string>>
     */
    protected function buildDependencyGraph(ModuleCollection $modules): array
    {
        $graph = [];

        foreach ($modules as $module) {
            $graph[$module->getName()] = $this->getValidDependencies($module, $modules);
        }

        return $graph;
    }

    /**
     * Get valid dependencies for a module (only those that exist in the collection).
     *
     * @return array<string>
     */
    protected function getValidDependencies(Module $module, ModuleCollection $modules): array
    {
        $dependencies = array_keys($module->getRequires());

        return array_values(array_filter(
            $dependencies,
            fn (string $dep) => $modules->hasModule($dep)
        ));
    }

    /**
     * Check if enabling a module would create a cycle.
     */
    public function wouldCreateCycle(Module $module, ModuleCollection $existing): bool
    {
        $testCollection = new ModuleCollection($existing->all());
        $testCollection->put($module->getName(), $module);

        return ! empty($this->detectCycles($testCollection));
    }

    /**
     * Get the dependency chain for a module (all transitive dependencies).
     *
     * @return array<string>
     */
    public function getDependencyChain(string $moduleName, ModuleCollection $modules): array
    {
        $module = $modules->findByName($moduleName);
        if (! $module) {
            return [];
        }

        $chain = [];
        $visited = [];
        $this->buildChain($module, $modules, $chain, $visited, 0);

        return $chain;
    }

    /**
     * Build dependency chain recursively with depth limit.
     * Uses hash set for O(1) visited check.
     *
     * @param  array<string>  $chain
     * @param  array<string, bool>  $visited  Hash set for O(1) lookup
     */
    protected function buildChain(
        Module $module,
        ModuleCollection $modules,
        array &$chain,
        array &$visited,
        int $depth,
    ): void {
        // Prevent infinite recursion with depth limit
        if ($depth >= self::MAX_CHAIN_DEPTH) {
            return;
        }

        $name = $module->getName();
        if (isset($visited[$name])) {
            return;
        }

        $visited[$name] = true;

        foreach (array_keys($module->getRequires()) as $depName) {
            $dep = $modules->findByName($depName);
            if ($dep) {
                $this->buildChain($dep, $modules, $chain, $visited, $depth + 1);
            }
        }

        $chain[] = $name;
    }

    /**
     * Get all modules that depend on the given module (reverse dependencies).
     *
     * @return array<string>
     */
    public function getDependents(string $moduleName, ModuleCollection $modules): array
    {
        $dependents = [];

        foreach ($modules as $module) {
            if (array_key_exists($moduleName, $module->getRequires())) {
                $dependents[] = $module->getName();
            }
        }

        return $dependents;
    }
}
