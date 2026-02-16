<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Facades;

use Esegments\ModularArchitecture\Links\LinkDefinition;
use Esegments\ModularArchitecture\Links\LinkRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Link Registry.
 *
 * Provides a clean API for defining cross-module links that replace
 * the boilerplate of ModuleLinkServiceProvider classes.
 *
 * Example usage:
 * ```php
 * Links::define('ProductBrandLink')
 *     ->requires('Products', 'Brands')
 *     ->belongsTo(Product::class, 'brand', Brand::class, 'brand_id')
 *     ->hasMany(Brand::class, 'products', Product::class, 'brand_id')
 *     ->productCountMacro(Brand::class);
 * ```
 *
 * @method static LinkDefinition define(string $name) Create a new link definition
 * @method static LinkRegistry register(LinkDefinition $definition) Register an existing definition
 * @method static LinkDefinition|null get(string $name) Get a link by name
 * @method static bool has(string $name) Check if a link exists
 * @method static Collection<string, LinkDefinition> all() Get all registered links
 * @method static Collection<string, LinkDefinition> enabled() Get all enabled links
 * @method static Collection<string, LinkDefinition> disabled() Get all disabled links
 * @method static bool isLinkEnabled(LinkDefinition|string $link) Check if a link is enabled
 * @method static Collection<string, LinkDefinition> forModel(string $modelClass) Get links for a model
 * @method static Collection<string, LinkDefinition> forResource(string $resourceClass) Get links for a resource
 * @method static void boot() Boot all enabled links
 * @method static bool isBooted() Check if registry is booted
 * @method static array status() Get status information for debugging
 * @method static array summary() Get count summary
 * @method static void clear() Clear all definitions (testing)
 * @method static bool forget(string $name) Remove a specific definition
 * @method static LinkRegistry setModuleChecker(callable $checker) Set the module checker callback
 * @method static LinkRegistry setRegistries(array $registries) Set Filament registry instances
 *
 * @see LinkRegistry
 */
class Links extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LinkRegistry::class;
    }
}
