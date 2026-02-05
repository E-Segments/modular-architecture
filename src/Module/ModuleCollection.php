<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Module;

use Illuminate\Support\Collection;

/**
 * @extends Collection<string, Module>
 */
class ModuleCollection extends Collection
{
    /**
     * Get only enabled modules.
     */
    public function enabled(): self
    {
        return $this->filter(fn (Module $module) => $module->isEnabled());
    }

    /**
     * Get only disabled modules.
     */
    public function disabled(): self
    {
        return $this->filter(fn (Module $module) => ! $module->isEnabled());
    }

    /**
     * Get only protected modules.
     */
    public function protected(): self
    {
        return $this->filter(fn (Module $module) => $module->isProtected());
    }

    /**
     * Sort modules by priority (higher priority = later in list).
     */
    public function sortByPriority(): self
    {
        return $this->sortBy(fn (Module $module) => $module->getPriority());
    }

    /**
     * Sort modules by priority descending (higher priority = first).
     */
    public function sortByPriorityDesc(): self
    {
        return $this->sortByDesc(fn (Module $module) => $module->getPriority());
    }

    /**
     * Sort modules by name alphabetically.
     */
    public function sortByName(): self
    {
        return $this->sortBy(fn (Module $module) => strtolower($module->getName()));
    }

    /**
     * Find module by name.
     */
    /**
     * Find a module by name.
     * O(1) lookup since collection is keyed by module name.
     */
    public function findByName(string $name): ?Module
    {
        return $this->get($name);
    }

    /**
     * Find module by alias.
     */
    public function findByAlias(string $alias): ?Module
    {
        return $this->first(fn (Module $module) => $module->getAlias() === $alias);
    }

    /**
     * Get modules that depend on the given module.
     */
    public function dependingOn(string $moduleName): self
    {
        return $this->filter(function (Module $module) use ($moduleName) {
            return array_key_exists($moduleName, $module->getRequires());
        });
    }

    /**
     * Get modules that the given module depends on.
     */
    public function dependenciesOf(string $moduleName): self
    {
        $module = $this->findByName($moduleName);
        if (! $module) {
            return new self;
        }

        $requires = array_keys($module->getRequires());

        return $this->filter(function (Module $m) use ($requires) {
            return in_array($m->getName(), $requires, true);
        });
    }

    /**
     * Get module names as array.
     */
    public function names(): array
    {
        return $this->map(fn (Module $module) => $module->getName())->values()->all();
    }

    /**
     * Get module aliases as array.
     */
    public function aliases(): array
    {
        return $this->map(fn (Module $module) => $module->getAlias())->values()->all();
    }

    /**
     * Get all service providers from all modules.
     *
     * @return array<string>
     */
    public function allProviders(): array
    {
        return $this->enabled()
            ->sortByPriority()
            ->flatMap(fn (Module $module) => $module->getProviders())
            ->values()
            ->all();
    }

    /**
     * Check if a module exists by name.
     */
    public function hasModule(string $name): bool
    {
        return $this->findByName($name) !== null;
    }

    /**
     * Check if a module is enabled by name.
     */
    public function isEnabled(string $name): bool
    {
        $module = $this->findByName($name);

        return $module !== null && $module->isEnabled();
    }
}
