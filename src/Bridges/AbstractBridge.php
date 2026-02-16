<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges;

use Esegments\ModularArchitecture\Bridges\Contracts\BridgeContract;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;

/**
 * Abstract base class for framework bridges.
 */
abstract class AbstractBridge implements BridgeContract
{
    /**
     * Registered modules and their discovered components.
     *
     * @var Collection<string, array<string, mixed>>
     */
    protected Collection $registered;

    /**
     * Whether the bridge was loaded from cache.
     */
    protected bool $loadedFromCache = false;

    public function __construct(
        protected readonly Application $app,
    ) {
        $this->registered = collect();
    }

    /**
     * Get the config key for this bridge.
     */
    abstract protected function configKey(): string;

    /**
     * Get the required class to check if framework is available.
     */
    abstract protected function requiredClass(): string;

    /**
     * Get the path within the module to scan for components.
     */
    abstract protected function componentPath(): string;

    public function isAvailable(): bool
    {
        return class_exists($this->requiredClass());
    }

    public function isEnabled(): bool
    {
        return (bool) config("modular.bridges.{$this->configKey()}.enabled", false);
    }

    /**
     * Check if logging is enabled for this bridge.
     */
    protected function shouldLog(): bool
    {
        return (bool) config('modular.debug', false);
    }

    /**
     * Log a debug message.
     */
    protected function log(string $message, array $context = []): void
    {
        if ($this->shouldLog()) {
            logger()->debug("[Modular:{$this->name()}] {$message}", $context);
        }
    }

    /**
     * Get all registered modules.
     *
     * @return Collection<string, array<string, mixed>>
     */
    public function getRegistered(): Collection
    {
        return $this->registered;
    }

    /**
     * Get discovered components for a specific module.
     *
     * @return array<string, mixed>|null
     */
    public function getModuleComponents(string $moduleName): ?array
    {
        return $this->registered->get($moduleName);
    }

    /**
     * Load bridge state from cached data.
     *
     * Override in subclasses to restore internal state.
     *
     * @param  array<string, mixed>  $cachedData
     */
    public function loadFromCache(array $cachedData): void
    {
        $this->registered = collect($cachedData['registered'] ?? []);
        $this->loadedFromCache = true;
    }

    /**
     * Get data to be cached.
     *
     * Override in subclasses to include additional state.
     *
     * @return array<string, mixed>
     */
    public function getCacheData(): array
    {
        return [
            'registered' => $this->registered->toArray(),
        ];
    }

    /**
     * Check if this bridge was loaded from cache.
     */
    public function wasLoadedFromCache(): bool
    {
        return $this->loadedFromCache;
    }

    /**
     * Get detailed status for inspection.
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        return [
            'name' => $this->name(),
            'available' => $this->isAvailable(),
            'enabled' => $this->isEnabled(),
            'config_key' => $this->configKey(),
            'required_class' => $this->requiredClass(),
            'component_path' => $this->componentPath(),
            'modules_count' => $this->registered->count(),
            'from_cache' => $this->loadedFromCache,
        ];
    }
}
