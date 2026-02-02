<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture;

use Esegments\ModularArchitecture\Cache\ModuleCache;
use Esegments\ModularArchitecture\Commands\CacheClearCommand;
use Esegments\ModularArchitecture\Commands\CacheCommand;
use Esegments\ModularArchitecture\Commands\DependentsCommand;
use Esegments\ModularArchitecture\Commands\DisableCommand;
use Esegments\ModularArchitecture\Commands\EnableCommand;
use Esegments\ModularArchitecture\Commands\GraphCommand;
use Esegments\ModularArchitecture\Commands\ListCommand;
use Esegments\ModularArchitecture\Commands\MakeModuleCommand;
use Esegments\ModularArchitecture\Commands\OptimizeCommand;
use Esegments\ModularArchitecture\Commands\RemoveCommand;
use Esegments\ModularArchitecture\Commands\StatusCommand;
use Esegments\ModularArchitecture\Commands\ValidateCommand;
use Esegments\ModularArchitecture\Discovery\ModuleDiscovery;
use Esegments\ModularArchitecture\Discovery\ModuleStateManager;
use Esegments\ModularArchitecture\Discovery\PathScanner;
use Esegments\ModularArchitecture\Registry\ModuleRegistry;
use Esegments\ModularArchitecture\Resolver\DependencyResolver;
use Esegments\ModularArchitecture\Scaffolding\ModuleGenerator;
use Esegments\ModularArchitecture\Scaffolding\StubProcessor;
use Esegments\ModularArchitecture\Validation\DependencyValidator;
use Esegments\ModularArchitecture\Validation\ModuleValidator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class ModularServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/modular.php',
            'modular'
        );

        $this->registerPathScanner();
        $this->registerStateManager();
        $this->registerDiscovery();
        $this->registerResolver();
        $this->registerRegistry();
        $this->registerValidators();
        $this->registerCache();
        $this->registerScaffolding();
        $this->registerModular();
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/modular.php' => config_path('modular.php'),
        ], 'modular-config');

        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/modular'),
        ], 'modular-stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeModuleCommand::class,
                ListCommand::class,
                StatusCommand::class,
                EnableCommand::class,
                DisableCommand::class,
                ValidateCommand::class,
                DependentsCommand::class,
                GraphCommand::class,
                RemoveCommand::class,
                CacheCommand::class,
                CacheClearCommand::class,
                OptimizeCommand::class,
            ]);
        }

        // Register module service providers if auto-discovery is enabled
        if (config('modular.discovery.auto', true)) {
            $this->registerModuleProviders();
        }
    }

    /**
     * Register the path scanner.
     */
    protected function registerPathScanner(): void
    {
        $this->app->singleton(PathScanner::class, function ($app) {
            return new PathScanner(
                paths: config('modular.paths', [base_path('Modules')]),
                exclude: config('modular.exclude', ['vendor', 'node_modules', 'tests']),
            );
        });
    }

    /**
     * Register the state manager.
     */
    protected function registerStateManager(): void
    {
        $this->app->singleton(ModuleStateManager::class, function ($app) {
            return new ModuleStateManager(
                files: $app->make(Filesystem::class),
                stateFile: config('modular.state_file', base_path('modules_statuses.json')),
                protectedModules: config('modular.protected', []),
            );
        });
    }

    /**
     * Register the module discovery.
     */
    protected function registerDiscovery(): void
    {
        $this->app->singleton(ModuleDiscovery::class, function ($app) {
            $discovery = new ModuleDiscovery(
                scanner: $app->make(PathScanner::class),
                stateManager: $app->make(ModuleStateManager::class),
            );

            if (config('modular.discovery.logging', false)) {
                $discovery->enableLogging();
            }

            return $discovery;
        });
    }

    /**
     * Register the dependency resolver.
     */
    protected function registerResolver(): void
    {
        $this->app->singleton(DependencyResolver::class, function () {
            return new DependencyResolver();
        });
    }

    /**
     * Register the module registry.
     */
    protected function registerRegistry(): void
    {
        $this->app->singleton(ModuleRegistry::class, function ($app) {
            return new ModuleRegistry(
                discovery: $app->make(ModuleDiscovery::class),
                stateManager: $app->make(ModuleStateManager::class),
                resolver: $app->make(DependencyResolver::class),
            );
        });
    }

    /**
     * Register validators.
     */
    protected function registerValidators(): void
    {
        $this->app->singleton(ModuleValidator::class, function () {
            return new ModuleValidator();
        });

        $this->app->singleton(DependencyValidator::class, function ($app) {
            return new DependencyValidator(
                resolver: $app->make(DependencyResolver::class),
            );
        });
    }

    /**
     * Register the cache.
     */
    protected function registerCache(): void
    {
        $this->app->singleton(ModuleCache::class, function ($app) {
            return new ModuleCache(
                cache: $app->make(CacheRepository::class),
                files: $app->make(Filesystem::class),
                cachePath: config('modular.cache.path', storage_path('framework/cache/modular')),
                ttl: config('modular.cache.ttl', 86400),
                enabled: config('modular.cache.enabled', true),
            );
        });
    }

    /**
     * Register scaffolding.
     */
    protected function registerScaffolding(): void
    {
        $this->app->singleton(StubProcessor::class, function ($app) {
            // Check for published stubs first, then fall back to package stubs
            $stubPath = is_dir(base_path('stubs/modular'))
                ? base_path('stubs/modular')
                : __DIR__ . '/../stubs';

            return new StubProcessor(
                files: $app->make(Filesystem::class),
                stubPath: $stubPath,
            );
        });

        $this->app->singleton(ModuleGenerator::class, function ($app) {
            return new ModuleGenerator(
                files: $app->make(Filesystem::class),
                stubProcessor: $app->make(StubProcessor::class),
                pathScanner: $app->make(PathScanner::class),
                config: config('modular.scaffolding', []),
            );
        });
    }

    /**
     * Register the main Modular class.
     */
    protected function registerModular(): void
    {
        $this->app->singleton(Modular::class, function ($app) {
            return new Modular(
                registry: $app->make(ModuleRegistry::class),
                generator: $app->make(ModuleGenerator::class),
                moduleValidator: $app->make(ModuleValidator::class),
                dependencyValidator: $app->make(DependencyValidator::class),
                cache: $app->make(ModuleCache::class),
            );
        });

        $this->app->alias(Modular::class, 'modular');
    }

    /**
     * Register module service providers.
     */
    protected function registerModuleProviders(): void
    {
        try {
            $modular = $this->app->make(Modular::class);
            $providers = $modular->getProviders();

            foreach ($providers as $provider) {
                if (class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        } catch (\Exception $e) {
            // Fail silently during boot - log if debug enabled
            if (config('modular.debug', false)) {
                logger()->error('[Modular] Failed to register module providers: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            Modular::class,
            'modular',
            ModuleRegistry::class,
            ModuleDiscovery::class,
            ModuleStateManager::class,
            DependencyResolver::class,
            ModuleValidator::class,
            DependencyValidator::class,
            ModuleCache::class,
            ModuleGenerator::class,
            StubProcessor::class,
            PathScanner::class,
        ];
    }
}
