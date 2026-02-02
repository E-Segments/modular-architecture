<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Resolver;

use Esegments\ModularArchitecture\Exceptions\CircularDependencyException;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleCollection;
use MJS\TopSort\Implementations\StringSort;

class DependencyResolver
{
    /**
     * Resolve module load order based on dependencies.
     */
    public function resolve(ModuleCollection $modules): ModuleCollection
    {
        if ($modules->isEmpty()) {
            return $modules;
        }

        // Detect cycles first
        $cycles = $this->detectCycles($modules);
        if (! empty($cycles)) {
            throw CircularDependencyException::forMultipleCycles($cycles);
        }

        // Build topological sort
        $sorter = new StringSort();

        foreach ($modules as $module) {
            $dependencies = array_keys($module->getRequires());

            // Only include dependencies that are in our module set
            $validDeps = array_filter(
                $dependencies,
                fn ($dep) => $modules->hasModule($dep)
            );

            $sorter->add($module->getName(), $validDeps);
        }

        try {
            $sorted = $sorter->sort();
        } catch (\Exception $e) {
            // Fallback to priority-based sorting
            return $modules->sortByPriority();
        }

        // Build new collection in sorted order, respecting priority within same level
        $ordered = new ModuleCollection();

        foreach ($sorted as $name) {
            $module = $modules->findByName($name);
            if ($module) {
                $ordered->put($name, $module);
            }
        }

        return $ordered;
    }

    /**
     * Detect circular dependencies.
     *
     * @return array<array<string>>
     */
    public function detectCycles(ModuleCollection $modules): array
    {
        $graph = [];

        foreach ($modules as $module) {
            $graph[$module->getName()] = array_keys($module->getRequires());
        }

        return $this->findCyclesInGraph($graph);
    }

    /**
     * Find cycles in a dependency graph using DFS.
     *
     * @param  array<string, array<string>>  $graph
     * @return array<array<string>>
     */
    protected function findCyclesInGraph(array $graph): array
    {
        $visited = [];
        $recursionStack = [];
        $cycles = [];

        foreach (array_keys($graph) as $node) {
            if (! isset($visited[$node])) {
                $this->dfs($node, $graph, $visited, $recursionStack, [], $cycles);
            }
        }

        return $cycles;
    }

    /**
     * Depth-first search for cycle detection.
     *
     * @param  array<string, array<string>>  $graph
     * @param  array<string, bool>  $visited
     * @param  array<string, bool>  $recursionStack
     * @param  array<string>  $path
     * @param  array<array<string>>  $cycles
     */
    protected function dfs(
        string $node,
        array $graph,
        array &$visited,
        array &$recursionStack,
        array $path,
        array &$cycles,
    ): void {
        $visited[$node] = true;
        $recursionStack[$node] = true;
        $path[] = $node;

        $dependencies = $graph[$node] ?? [];

        foreach ($dependencies as $dependency) {
            // Only consider dependencies that exist in our graph
            if (! isset($graph[$dependency])) {
                continue;
            }

            if (! isset($visited[$dependency])) {
                $this->dfs($dependency, $graph, $visited, $recursionStack, $path, $cycles);
            } elseif (isset($recursionStack[$dependency])) {
                // Found a cycle - extract the cycle path
                $cycleStart = array_search($dependency, $path, true);
                if ($cycleStart !== false) {
                    $cyclePath = array_slice($path, $cycleStart);
                    $cyclePath[] = $dependency; // Complete the cycle
                    $cycles[] = $cyclePath;
                }
            }
        }

        $recursionStack[$node] = false;
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
     * Get the dependency chain for a module.
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
        $this->buildChain($module, $modules, $chain, []);

        return $chain;
    }

    /**
     * Build dependency chain recursively.
     *
     * @param  array<string>  $chain
     * @param  array<string>  $visited
     */
    protected function buildChain(
        Module $module,
        ModuleCollection $modules,
        array &$chain,
        array $visited,
    ): void {
        if (in_array($module->getName(), $visited, true)) {
            return;
        }

        $visited[] = $module->getName();

        foreach (array_keys($module->getRequires()) as $depName) {
            $dep = $modules->findByName($depName);
            if ($dep) {
                $this->buildChain($dep, $modules, $chain, $visited);
            }
        }

        $chain[] = $module->getName();
    }
}
