<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges;

use Esegments\ModularArchitecture\Bridges\Cache\BridgeCache;
use Esegments\ModularArchitecture\Bridges\Contracts\BridgeContract;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;

/**
 * Manages all framework bridges.
 *
 * Bridges are auto-discovered and registered based on configuration.
 * Each bridge is responsible for integrating module components with
 * a specific Laravel ecosystem framework (Livewire, Filament, etc.).
 */
class BridgeManager
{
    /**
     * Registered bridges.
     *
     * @var Collection<string, BridgeContract>
     */
    protected Collection $bridges;

    /**
     * Whether bridges have been booted.
     */
    protected bool $booted = false;

    /**
     * Whether data was loaded from cache.
     */
    protected bool $loadedFromCache = false;

    /**
     * Bridge cache instance.
     */
    protected ?BridgeCache $cache = null;

    public function __construct(
        protected readonly Application $app,
    ) {
        $this->bridges = collect();
    }

    /**
     * Set the cache instance.
     */
    public function setCache(BridgeCache $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Get the cache instance.
     */
    public function getCache(): ?BridgeCache
    {
        return $this->cache;
    }

    /**
     * Load bridges from cache if available.
     */
    public function loadFromCache(): bool
    {
        if (! $this->cache || ! $this->cache->isEnabled()) {
            return false;
        }

        $cachedData = $this->cache->get();

        if (! $cachedData) {
            return false;
        }

        foreach ($this->bridges as $name => $bridge) {
            if (isset($cachedData[$name]) && $bridge instanceof AbstractBridge) {
                $bridge->loadFromCache($cachedData[$name]);
            }
        }

        $this->loadedFromCache = true;

        return true;
    }

    /**
     * Save current bridge state to cache.
     */
    public function saveToCache(): void
    {
        if (! $this->cache || ! $this->cache->isEnabled()) {
            return;
        }

        $data = [];

        foreach ($this->bridges as $name => $bridge) {
            if ($bridge instanceof AbstractBridge) {
                $data[$name] = $bridge->getCacheData();
            }
        }

        $this->cache->put($data);
    }

    /**
     * Clear the bridge cache.
     */
    public function clearCache(): void
    {
        $this->cache?->clear();
    }

    /**
     * Check if data was loaded from cache.
     */
    public function wasLoadedFromCache(): bool
    {
        return $this->loadedFromCache;
    }

    /**
     * Register a bridge.
     */
    public function register(string $name, BridgeContract $bridge): self
    {
        $this->bridges->put($name, $bridge);

        return $this;
    }

    /**
     * Get a registered bridge.
     */
    public function get(string $name): ?BridgeContract
    {
        return $this->bridges->get($name);
    }

    /**
     * Get all registered bridges.
     *
     * @return Collection<string, BridgeContract>
     */
    public function all(): Collection
    {
        return $this->bridges;
    }

    /**
     * Get all enabled and available bridges.
     *
     * @return Collection<string, BridgeContract>
     */
    public function enabled(): Collection
    {
        return $this->bridges->filter(
            fn (BridgeContract $bridge) => $bridge->isAvailable() && $bridge->isEnabled()
        );
    }

    /**
     * Register a module with all enabled bridges.
     */
    public function registerModule(Module $module): void
    {
        foreach ($this->enabled() as $bridge) {
            try {
                $bridge->registerModule($module);
            } catch (\Throwable $e) {
                if (config('modular.debug', false)) {
                    logger()->error("[Modular:BridgeManager] Failed to register module {$module->getName()} with {$bridge->name()}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Boot all enabled bridges.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->enabled() as $bridge) {
            try {
                $bridge->boot();
            } catch (\Throwable $e) {
                if (config('modular.debug', false)) {
                    logger()->error("[Modular:BridgeManager] Failed to boot {$bridge->name()}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->booted = true;
    }

    /**
     * Check if bridges have been booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Get status of all bridges for debugging.
     *
     * @return array<string, array{available: bool, enabled: bool, components: int}>
     */
    public function status(): array
    {
        return $this->bridges->mapWithKeys(function (BridgeContract $bridge) {
            $componentCount = 0;
            if ($bridge instanceof AbstractBridge) {
                $componentCount = $bridge->getRegistered()->count();
            }

            return [
                $bridge->name() => [
                    'available' => $bridge->isAvailable(),
                    'enabled' => $bridge->isEnabled(),
                    'components' => $componentCount,
                ],
            ];
        })->toArray();
    }
}
