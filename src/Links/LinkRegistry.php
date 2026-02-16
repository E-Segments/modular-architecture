<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Links;

use Illuminate\Support\Collection;

/**
 * Central registry for all link definitions.
 *
 * The LinkRegistry is a singleton that stores all LinkDefinitions
 * and handles conditional activation based on module availability.
 *
 * Example usage:
 * ```php
 * // Define a link
 * app(LinkRegistry::class)->define('ProductBrandLink')
 *     ->requires('Products', 'Brands')
 *     ->belongsTo(Product::class, 'brand', Brand::class);
 *
 * // Or via facade
 * Links::define('ProductBrandLink')
 *     ->requires('Products', 'Brands')
 *     ->belongsTo(Product::class, 'brand', Brand::class);
 *
 * // Boot all enabled links
 * app(LinkRegistry::class)->boot();
 * ```
 */
class LinkRegistry
{
    /**
     * Registered link definitions.
     *
     * @var Collection<string, LinkDefinition>
     */
    protected Collection $definitions;

    /**
     * Whether the registry has been booted.
     */
    protected bool $booted = false;

    /**
     * Callback to check if modules are enabled.
     *
     * @var callable|null
     */
    protected $moduleChecker = null;

    /**
     * Optional registry instances for Filament integrations.
     *
     * @var array<string, object>
     */
    protected array $registries = [];

    /**
     * Create a new link registry.
     */
    public function __construct()
    {
        $this->definitions = collect();
    }

    /**
     * Set the module checker callback.
     *
     * The callback receives an array of module names and returns true
     * if all modules are enabled.
     *
     * @param  callable  $checker  Callback: fn(array $modules): bool
     */
    public function setModuleChecker(callable $checker): self
    {
        $this->moduleChecker = $checker;

        return $this;
    }

    /**
     * Set registry instances for Filament integrations.
     *
     * @param  array<string, object>  $registries
     */
    public function setRegistries(array $registries): self
    {
        $this->registries = $registries;

        return $this;
    }

    /**
     * Define a new link.
     *
     * Creates a new LinkDefinition and registers it with the registry.
     * Returns the definition for fluent chaining.
     */
    public function define(string $name): LinkDefinition
    {
        $definition = new LinkDefinition($name);
        $definition->setRegistries($this->registries);
        $this->definitions->put($name, $definition);

        return $definition;
    }

    /**
     * Register an existing link definition.
     */
    public function register(LinkDefinition $definition): self
    {
        $definition->setRegistries($this->registries);
        $this->definitions->put($definition->getName(), $definition);

        return $this;
    }

    /**
     * Get a link definition by name.
     */
    public function get(string $name): ?LinkDefinition
    {
        return $this->definitions->get($name);
    }

    /**
     * Check if a link is registered.
     */
    public function has(string $name): bool
    {
        return $this->definitions->has($name);
    }

    /**
     * Get all registered link definitions.
     *
     * @return Collection<string, LinkDefinition>
     */
    public function all(): Collection
    {
        return $this->definitions;
    }

    /**
     * Get all links that are enabled (all required modules are active).
     *
     * @return Collection<string, LinkDefinition>
     */
    public function enabled(): Collection
    {
        return $this->definitions->filter(function (LinkDefinition $definition) {
            return $this->isLinkEnabled($definition);
        });
    }

    /**
     * Get all links that are disabled (missing required modules).
     *
     * @return Collection<string, LinkDefinition>
     */
    public function disabled(): Collection
    {
        return $this->definitions->filter(function (LinkDefinition $definition) {
            return ! $this->isLinkEnabled($definition);
        });
    }

    /**
     * Check if a specific link is enabled.
     */
    public function isLinkEnabled(LinkDefinition|string $link): bool
    {
        if (is_string($link)) {
            $link = $this->get($link);

            if ($link === null) {
                return false;
            }
        }

        $requiredModules = $link->getRequiredModules();

        if (empty($requiredModules)) {
            return true;
        }

        // Use custom checker if provided
        if ($this->moduleChecker !== null) {
            return ($this->moduleChecker)($requiredModules);
        }

        // Default: check if ModuleStatusCache is available
        if (class_exists('Modules\\Core\\Services\\ModuleStatusCache')) {
            return \Modules\Core\Services\ModuleStatusCache::allEnabled($requiredModules);
        }

        // Fallback: assume all modules are enabled if no checker available
        return true;
    }

    /**
     * Get all links that affect a specific model class.
     *
     * @param  class-string  $modelClass  The model class
     * @return Collection<string, LinkDefinition>
     */
    public function forModel(string $modelClass): Collection
    {
        // This is a placeholder - would need to track model associations
        // in LinkDefinition to implement fully
        return $this->enabled();
    }

    /**
     * Get all links that affect a specific Filament resource.
     *
     * @param  class-string  $resourceClass  The resource class
     * @return Collection<string, LinkDefinition>
     */
    public function forResource(string $resourceClass): Collection
    {
        // This is a placeholder - would need to track resource associations
        // in LinkDefinition to implement fully
        return $this->enabled();
    }

    /**
     * Boot all enabled link definitions.
     *
     * This registers all relationships, macros, and integrations
     * for links whose required modules are all enabled.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->enabled() as $definition) {
            try {
                $definition->boot();
            } catch (\Throwable $e) {
                // Log error but continue booting other links
                if (function_exists('config') && config('app.debug', false)) {
                    if (function_exists('logger')) {
                        logger()->error("[LinkRegistry] Failed to boot link: {$definition->getName()}", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }
        }

        $this->booted = true;
    }

    /**
     * Check if the registry has been booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Get status information for debugging.
     *
     * @return array<string, array{enabled: bool, booted: bool, requiredModules: array<string>}>
     */
    public function status(): array
    {
        return $this->definitions->mapWithKeys(function (LinkDefinition $definition) {
            return [
                $definition->getName() => [
                    'enabled' => $this->isLinkEnabled($definition),
                    'booted' => $definition->isBooted(),
                    'requiredModules' => $definition->getRequiredModules(),
                ],
            ];
        })->toArray();
    }

    /**
     * Get a count summary.
     *
     * @return array{total: int, enabled: int, disabled: int, booted: int}
     */
    public function summary(): array
    {
        return [
            'total' => $this->definitions->count(),
            'enabled' => $this->enabled()->count(),
            'disabled' => $this->disabled()->count(),
            'booted' => $this->definitions->filter(fn (LinkDefinition $d) => $d->isBooted())->count(),
        ];
    }

    /**
     * Clear all registered definitions.
     *
     * Primarily useful for testing.
     */
    public function clear(): void
    {
        $this->definitions = collect();
        $this->booted = false;
    }

    /**
     * Remove a specific link definition.
     */
    public function forget(string $name): bool
    {
        if ($this->definitions->has($name)) {
            $this->definitions->forget($name);

            return true;
        }

        return false;
    }
}
