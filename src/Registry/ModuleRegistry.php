<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Registry;

use Esegments\ModularArchitecture\Discovery\ModuleDiscovery;
use Esegments\ModularArchitecture\Discovery\ModuleStateManager;
use Esegments\ModularArchitecture\Exceptions\DependencyException;
use Esegments\ModularArchitecture\Exceptions\ModuleNotFoundException;
use Esegments\ModularArchitecture\Exceptions\ProtectedModuleException;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleCollection;
use Esegments\ModularArchitecture\Resolver\DependencyResolver;

class ModuleRegistry
{
    protected ?ModuleCollection $ordered = null;

    public function __construct(
        protected readonly ModuleDiscovery $discovery,
        protected readonly ModuleStateManager $stateManager,
        protected readonly DependencyResolver $resolver,
    ) {}

    /**
     * Get all modules.
     */
    public function all(): ModuleCollection
    {
        return $this->discovery->discover();
    }

    /**
     * Get all enabled modules in load order.
     */
    public function enabled(): ModuleCollection
    {
        if ($this->ordered !== null) {
            return $this->ordered;
        }

        $enabled = $this->discovery->enabled();
        $this->ordered = $this->resolver->resolve($enabled);

        return $this->ordered;
    }

    /**
     * Get all disabled modules.
     */
    public function disabled(): ModuleCollection
    {
        return $this->discovery->disabled();
    }

    /**
     * Find a module by name.
     */
    public function find(string $name): ?Module
    {
        return $this->discovery->find($name);
    }

    /**
     * Find a module by name or throw exception.
     */
    public function findOrFail(string $name): Module
    {
        $module = $this->find($name);

        if (! $module) {
            throw ModuleNotFoundException::forModule($name);
        }

        return $module;
    }

    /**
     * Check if a module exists.
     */
    public function exists(string $name): bool
    {
        return $this->discovery->exists($name);
    }

    /**
     * Check if a module is enabled.
     */
    public function isEnabled(string $name): bool
    {
        $module = $this->find($name);

        return $module !== null && $module->isEnabled();
    }

    /**
     * Check if a module is protected.
     */
    public function isProtected(string $name): bool
    {
        return $this->stateManager->isProtected($name);
    }

    /**
     * Enable a module.
     */
    public function enable(string $name): Module
    {
        $module = $this->findOrFail($name);

        // Check if dependencies are enabled
        foreach (array_keys($module->getRequires()) as $dependency) {
            if (! $this->isEnabled($dependency)) {
                throw DependencyException::disabled($name, $dependency);
            }
        }

        $module->enable();
        $this->stateManager->enable($name);
        $this->clearCache();

        return $module;
    }

    /**
     * Disable a module.
     */
    public function disable(string $name): Module
    {
        $module = $this->findOrFail($name);

        if ($module->isProtected()) {
            throw ProtectedModuleException::cannotDisable($name);
        }

        // Check if other enabled modules depend on this
        $dependents = $this->getDependents($name)->enabled();
        if ($dependents->isNotEmpty()) {
            throw new DependencyException(
                moduleName: $name,
                dependencyName: $dependents->first()->getName(),
                message: "Cannot disable [{$name}] - the following modules depend on it: " . implode(', ', $dependents->names()),
            );
        }

        $module->disable();
        $this->stateManager->disable($name);
        $this->clearCache();

        return $module;
    }

    /**
     * Get modules that depend on the given module.
     */
    public function getDependents(string $name): ModuleCollection
    {
        return $this->all()->dependingOn($name);
    }

    /**
     * Get modules that the given module depends on.
     */
    public function getDependencies(string $name): ModuleCollection
    {
        return $this->all()->dependenciesOf($name);
    }

    /**
     * Get all service providers from enabled modules in load order.
     *
     * @return array<string>
     */
    public function getProviders(): array
    {
        return $this->enabled()->allProviders();
    }

    /**
     * Refresh the registry.
     */
    public function refresh(): void
    {
        $this->clearCache();
        $this->discovery->refresh();
    }

    /**
     * Clear the ordered cache.
     */
    public function clearCache(): void
    {
        $this->ordered = null;
    }

    /**
     * Get the discovery instance.
     */
    public function getDiscovery(): ModuleDiscovery
    {
        return $this->discovery;
    }

    /**
     * Get the state manager.
     */
    public function getStateManager(): ModuleStateManager
    {
        return $this->stateManager;
    }

    /**
     * Get the resolver.
     */
    public function getResolver(): DependencyResolver
    {
        return $this->resolver;
    }

    /**
     * Get module count.
     */
    public function count(): int
    {
        return $this->all()->count();
    }

    /**
     * Get enabled module count.
     */
    public function enabledCount(): int
    {
        return $this->enabled()->count();
    }
}
