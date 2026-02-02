<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture;

use Esegments\ModularArchitecture\Cache\ModuleCache;
use Esegments\ModularArchitecture\Discovery\ModuleDiscovery;
use Esegments\ModularArchitecture\Discovery\ModuleStateManager;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleCollection;
use Esegments\ModularArchitecture\Registry\ModuleRegistry;
use Esegments\ModularArchitecture\Resolver\DependencyResolver;
use Esegments\ModularArchitecture\Scaffolding\ModuleGenerator;
use Esegments\ModularArchitecture\Validation\DependencyValidator;
use Esegments\ModularArchitecture\Validation\ModuleValidator;
use Esegments\ModularArchitecture\Validation\ValidationResult;

class Modular
{
    public function __construct(
        protected readonly ModuleRegistry $registry,
        protected readonly ModuleGenerator $generator,
        protected readonly ModuleValidator $moduleValidator,
        protected readonly DependencyValidator $dependencyValidator,
        protected readonly ModuleCache $cache,
    ) {}

    /**
     * Get all modules.
     */
    public function all(): ModuleCollection
    {
        return $this->registry->all();
    }

    /**
     * Get all enabled modules in load order.
     */
    public function enabled(): ModuleCollection
    {
        return $this->registry->enabled();
    }

    /**
     * Get all disabled modules.
     */
    public function disabled(): ModuleCollection
    {
        return $this->registry->disabled();
    }

    /**
     * Find a module by name.
     */
    public function find(string $name): ?Module
    {
        return $this->registry->find($name);
    }

    /**
     * Find a module by name or throw exception.
     */
    public function findOrFail(string $name): Module
    {
        return $this->registry->findOrFail($name);
    }

    /**
     * Check if a module exists.
     */
    public function exists(string $name): bool
    {
        return $this->registry->exists($name);
    }

    /**
     * Check if a module is enabled.
     */
    public function isEnabled(string $name): bool
    {
        return $this->registry->isEnabled($name);
    }

    /**
     * Check if a module is protected.
     */
    public function isProtected(string $name): bool
    {
        return $this->registry->isProtected($name);
    }

    /**
     * Enable a module.
     */
    public function enable(string $name): Module
    {
        $module = $this->registry->enable($name);
        $this->cache->clear();

        return $module;
    }

    /**
     * Disable a module.
     */
    public function disable(string $name): Module
    {
        $module = $this->registry->disable($name);
        $this->cache->clear();

        return $module;
    }

    /**
     * Create a new module.
     */
    public function create(string $name, array $options = []): Module
    {
        $module = $this->generator->generate($name, $options);
        $this->registry->refresh();
        $this->cache->clear();

        return $module;
    }

    /**
     * Delete a module.
     */
    public function delete(string $name, bool $force = false): void
    {
        $module = $this->findOrFail($name);

        // Validate removal
        $result = $this->dependencyValidator->canRemove($module, $this->all());
        if (! $result->isValid() && ! $force) {
            throw new \RuntimeException(implode('; ', $result->errors));
        }

        $this->generator->delete($module->getPath());
        $this->registry->getStateManager()->removeState($name);
        $this->registry->refresh();
        $this->cache->clear();
    }

    /**
     * Get modules that depend on the given module.
     */
    public function getDependents(string $name): ModuleCollection
    {
        return $this->registry->getDependents($name);
    }

    /**
     * Get modules that the given module depends on.
     */
    public function getDependencies(string $name): ModuleCollection
    {
        return $this->registry->getDependencies($name);
    }

    /**
     * Validate a specific module.
     */
    public function validate(string $name): ValidationResult
    {
        $module = $this->findOrFail($name);

        $moduleResult = $this->moduleValidator->validate($module);
        $depResult = $this->dependencyValidator->validateModule($module, $this->all());

        return $moduleResult->merge($depResult);
    }

    /**
     * Validate all modules.
     */
    public function validateAll(): ValidationResult
    {
        $result = new ValidationResult(true);

        foreach ($this->all() as $module) {
            $moduleResult = $this->moduleValidator->validate($module);
            $result = $result->merge($moduleResult);
        }

        $depResult = $this->dependencyValidator->validate($this->all());

        return $result->merge($depResult);
    }

    /**
     * Get all service providers from enabled modules.
     *
     * @return array<string>
     */
    public function getProviders(): array
    {
        return $this->registry->getProviders();
    }

    /**
     * Refresh module discovery.
     */
    public function refresh(): void
    {
        $this->registry->refresh();
        $this->cache->clear();
    }

    /**
     * Build production cache.
     */
    public function cache(): void
    {
        $modules = $this->enabled();
        $this->cache->buildManifest($modules);
        $this->cache->put($modules);
    }

    /**
     * Clear all caches.
     */
    public function clearCache(): void
    {
        $this->cache->clear();
        $this->registry->clearCache();
    }

    /**
     * Get the registry.
     */
    public function getRegistry(): ModuleRegistry
    {
        return $this->registry;
    }

    /**
     * Get the resolver.
     */
    public function getResolver(): DependencyResolver
    {
        return $this->registry->getResolver();
    }

    /**
     * Get the discovery.
     */
    public function getDiscovery(): ModuleDiscovery
    {
        return $this->registry->getDiscovery();
    }

    /**
     * Get the state manager.
     */
    public function getStateManager(): ModuleStateManager
    {
        return $this->registry->getStateManager();
    }

    /**
     * Get module count.
     */
    public function count(): int
    {
        return $this->registry->count();
    }

    /**
     * Get enabled module count.
     */
    public function enabledCount(): int
    {
        return $this->registry->enabledCount();
    }
}
