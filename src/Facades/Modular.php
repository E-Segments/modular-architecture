<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Facades;

use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleCollection;
use Esegments\ModularArchitecture\Validation\ValidationResult;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ModuleCollection all()
 * @method static ModuleCollection enabled()
 * @method static ModuleCollection disabled()
 * @method static Module|null find(string $name)
 * @method static Module findOrFail(string $name)
 * @method static bool exists(string $name)
 * @method static bool isEnabled(string $name)
 * @method static bool isProtected(string $name)
 * @method static Module enable(string $name)
 * @method static Module disable(string $name)
 * @method static Module create(string $name, array $options = [])
 * @method static void delete(string $name, bool $force = false)
 * @method static ModuleCollection getDependents(string $name)
 * @method static ModuleCollection getDependencies(string $name)
 * @method static ValidationResult validate(string $name)
 * @method static ValidationResult validateAll()
 * @method static array getProviders()
 * @method static void refresh()
 * @method static void cache()
 * @method static void clearCache()
 * @method static int count()
 * @method static int enabledCount()
 *
 * @see \Esegments\ModularArchitecture\Modular
 */
class Modular extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Esegments\ModularArchitecture\Modular::class;
    }
}
