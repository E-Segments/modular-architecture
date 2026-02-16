<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Registry;

use Esegments\ModularArchitecture\Discovery\ModuleDiscovery;
use Esegments\ModularArchitecture\Discovery\ModuleStateManager;
use Esegments\ModularArchitecture\Exceptions\DependencyException;
use Esegments\ModularArchitecture\Exceptions\ExtensionVetoException;
use Esegments\ModularArchitecture\Exceptions\ModuleNotFoundException;
use Esegments\ModularArchitecture\Exceptions\ProtectedModuleException;
use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleDisable;
use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleEnable;
use Esegments\ModularArchitecture\Extensions\Points\ModuleDisabled;
use Esegments\ModularArchitecture\Extensions\Points\ModuleEnabled;
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
     *
     * @throws DependencyException If a required dependency is not enabled
     * @throws ExtensionVetoException If an extension handler vetoed the operation
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

        // Dispatch BeforeModuleEnable extension point (if available)
        if ($this->hasExtensionsSupport()) {
            $beforeEnable = new BeforeModuleEnable($module, $name);
            $canProceed = $this->dispatchInterruptible($beforeEnable);

            if (! $canProceed || $beforeEnable->hasErrors()) {
                throw ExtensionVetoException::forEnable(
                    $name,
                    $beforeEnable->errors,
                    $beforeEnable->getInterruptedBy(),
                );
            }
        }

        $module->enable();
        $this->stateManager->enable($name);
        $this->clearCache();

        // Dispatch ModuleEnabled extension point (if available)
        if ($this->hasExtensionsSupport()) {
            $this->dispatch(new ModuleEnabled($module, $name));
        }

        return $module;
    }

    /**
     * Disable a module.
     *
     * @throws ProtectedModuleException If the module is protected
     * @throws DependencyException If other enabled modules depend on this module
     * @throws ExtensionVetoException If an extension handler vetoed the operation
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
                message: "Cannot disable [{$name}] - the following modules depend on it: ".implode(', ', $dependents->names()),
            );
        }

        // Dispatch BeforeModuleDisable extension point (if available)
        if ($this->hasExtensionsSupport()) {
            $beforeDisable = new BeforeModuleDisable($module, $name);
            $canProceed = $this->dispatchInterruptible($beforeDisable);

            if (! $canProceed || $beforeDisable->hasErrors()) {
                throw ExtensionVetoException::forDisable(
                    $name,
                    $beforeDisable->errors,
                    $beforeDisable->getInterruptedBy(),
                );
            }
        }

        $module->disable();
        $this->stateManager->disable($name);
        $this->clearCache();

        // Dispatch ModuleDisabled extension point (if available)
        if ($this->hasExtensionsSupport()) {
            $this->dispatch(new ModuleDisabled($module, $name));
        }

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

    /**
     * Check if the extensions package is available.
     */
    protected function hasExtensionsSupport(): bool
    {
        return class_exists(\Esegments\LaravelExtensions\ExtensionDispatcher::class)
            && app()->bound(\Esegments\LaravelExtensions\ExtensionDispatcher::class);
    }

    /**
     * Dispatch an extension point.
     */
    protected function dispatch(object $extensionPoint): object
    {
        return app(\Esegments\LaravelExtensions\ExtensionDispatcher::class)->dispatch($extensionPoint);
    }

    /**
     * Dispatch an interruptible extension point and return whether to proceed.
     */
    protected function dispatchInterruptible(object $extensionPoint): bool
    {
        return app(\Esegments\LaravelExtensions\ExtensionDispatcher::class)->dispatchInterruptible($extensionPoint);
    }
}
