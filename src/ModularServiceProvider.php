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
use Esegments\ModularArchitecture\Commands\InstallCommand;
use Esegments\ModularArchitecture\Commands\ListCommand;
use Esegments\ModularArchitecture\Commands\MakeModuleCommand;
use Esegments\ModularArchitecture\Commands\OptimizeCommand;
use Esegments\ModularArchitecture\Commands\OutdatedCommand;
use Esegments\ModularArchitecture\Commands\Overrides\ControllerMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\EventMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\FactoryMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\JobMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\ListenerMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\MailMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\MigrationMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\ModelMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\NotificationMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\PolicyMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\RequestMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\ResourceMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\RuleMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\SeederMakeCommand;
use Esegments\ModularArchitecture\Commands\Overrides\TestMakeCommand;
use Esegments\ModularArchitecture\Commands\RemoveCommand;
use Esegments\ModularArchitecture\Commands\StatusCommand;
use Esegments\ModularArchitecture\Commands\StorageMigrateCommand;
use Esegments\ModularArchitecture\Commands\UpdateCommand;
use Esegments\ModularArchitecture\Commands\ValidateCommand;
use Esegments\ModularArchitecture\Contracts\GitHubClientContract;
use Esegments\ModularArchitecture\Contracts\ModuleStorageContract;
use Esegments\ModularArchitecture\Discovery\ModuleDiscovery;
use Esegments\ModularArchitecture\Discovery\ModuleStateManager;
use Esegments\ModularArchitecture\Discovery\PathScanner;
use Esegments\ModularArchitecture\GitHub\ArchiveHandler;
use Esegments\ModularArchitecture\GitHub\GitHubClient;
use Esegments\ModularArchitecture\GitHub\ModuleBackup;
use Esegments\ModularArchitecture\GitHub\ModuleInstaller;
use Esegments\ModularArchitecture\Module\ModuleLoader;
use Esegments\ModularArchitecture\Registry\ModuleRegistry;
use Esegments\ModularArchitecture\Resolver\DependencyResolver;
use Esegments\ModularArchitecture\Scaffolding\ModuleGenerator;
use Esegments\ModularArchitecture\Scaffolding\StubProcessor;
use Esegments\ModularArchitecture\Storage\StorageManager;
use Esegments\ModularArchitecture\Support\OctaneSupport;
use Esegments\ModularArchitecture\Validation\DependencyValidator;
use Esegments\ModularArchitecture\Validation\ModuleValidator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Filesystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ModularServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package using spatie/laravel-package-tools.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('modular-architecture')
            ->hasConfigFile('modular')
            ->hasCommands([
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
                InstallCommand::class,
                UpdateCommand::class,
                OutdatedCommand::class,
                StorageMigrateCommand::class,
            ]);

        // Register publishables
        $this->publishes([
            __DIR__.'/../stubs' => base_path('stubs/modular'),
        ], "{$package->shortName()}-stubs");

        $this->publishes([
            __DIR__.'/../database/migrations/create_modules_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_modules_table.php'),
        ], "{$package->shortName()}-migrations");
    }

    /**
     * Register all package services.
     */
    public function packageRegistered(): void
    {
        $this->registerStorage();
        $this->registerPathScanner();
        $this->registerStateManager();
        $this->registerLoader();
        $this->registerDiscovery();
        $this->registerResolver();
        $this->registerRegistry();
        $this->registerValidators();
        $this->registerCache();
        $this->registerScaffolding();
        $this->registerGitHub();
        $this->registerModular();
    }

    /**
     * Boot package services.
     */
    public function packageBooted(): void
    {
        // Register command overrides if enabled
        if (config('modular.commands.override', true)) {
            $this->registerCommandOverrides();
        }

        // Register module service providers if auto-discovery is enabled
        if (config('modular.discovery.auto', true)) {
            $this->registerModuleProviders();
        }

        // Register Octane listeners if Octane is installed
        $this->registerOctaneListeners();
    }

    /**
     * Register Octane event listeners for cache management.
     */
    protected function registerOctaneListeners(): void
    {
        if (! class_exists('Laravel\Octane\Events\WorkerStarting')) {
            return;
        }

        $this->app['events']->listen(
            'Laravel\Octane\Events\WorkerStarting',
            function ($event) {
                OctaneSupport::warm($this->app);
            }
        );

        // Optionally listen for tick events to refresh state periodically
        if (config('modular.octane.refresh_on_tick', false)) {
            $this->app['events']->listen(
                'Laravel\Octane\Events\TickReceived',
                function ($event) {
                    OctaneSupport::flush($this->app);
                }
            );
        }
    }

    /**
     * Register command overrides for make:* commands with --module support.
     */
    protected function registerCommandOverrides(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Override Laravel's make:* commands with module-aware versions
        $this->app->extend('command.model.make', fn () => $this->app->make(ModelMakeCommand::class));
        $this->app->extend('command.controller.make', fn () => $this->app->make(ControllerMakeCommand::class));
        $this->app->extend('command.migrate.make', fn () => $this->app->make(MigrationMakeCommand::class));
        $this->app->extend('command.seeder.make', fn () => $this->app->make(SeederMakeCommand::class));
        $this->app->extend('command.factory.make', fn () => $this->app->make(FactoryMakeCommand::class));
        $this->app->extend('command.policy.make', fn () => $this->app->make(PolicyMakeCommand::class));
        $this->app->extend('command.event.make', fn () => $this->app->make(EventMakeCommand::class));
        $this->app->extend('command.listener.make', fn () => $this->app->make(ListenerMakeCommand::class));
        $this->app->extend('command.job.make', fn () => $this->app->make(JobMakeCommand::class));
        $this->app->extend('command.notification.make', fn () => $this->app->make(NotificationMakeCommand::class));
        $this->app->extend('command.mail.make', fn () => $this->app->make(MailMakeCommand::class));
        $this->app->extend('command.rule.make', fn () => $this->app->make(RuleMakeCommand::class));
        $this->app->extend('command.request.make', fn () => $this->app->make(RequestMakeCommand::class));
        $this->app->extend('command.resource.make', fn () => $this->app->make(ResourceMakeCommand::class));
        $this->app->extend('command.test.make', fn () => $this->app->make(TestMakeCommand::class));
    }

    /**
     * Register storage manager.
     */
    protected function registerStorage(): void
    {
        $this->app->singleton(StorageManager::class, function ($app) {
            return new StorageManager(
                files: $app->make(Filesystem::class),
                config: config('modular.storage', []),
            );
        });

        $this->app->bind(ModuleStorageContract::class, function ($app) {
            return $app->make(StorageManager::class)->driver();
        });
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
                stateFile: config('modular.storage.states_file', storage_path('modular/modules_statuses.json')),
                protectedModules: config('modular.protected', []),
            );
        });
    }

    /**
     * Register the module loader.
     */
    protected function registerLoader(): void
    {
        $this->app->singleton(ModuleLoader::class, function ($app) {
            return new ModuleLoader(
                files: $app->make(Filesystem::class),
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
                loader: $app->make(ModuleLoader::class),
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
            return new DependencyResolver;
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
            return new ModuleValidator;
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
                cachePath: config('modular.cache.path', storage_path('modular/cache')),
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
                : __DIR__.'/../stubs';

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
     * Register GitHub integration.
     */
    protected function registerGitHub(): void
    {
        $this->app->singleton(GitHubClient::class, function ($app) {
            return new GitHubClient(
                token: config('modular.github.token'),
                timeout: config('modular.github.timeout', 30),
                verifySsl: config('modular.github.verify_ssl', true),
            );
        });

        // Bind interface to implementation
        $this->app->bind(GitHubClientContract::class, GitHubClient::class);

        $this->app->singleton(ArchiveHandler::class, function ($app) {
            return new ArchiveHandler(
                github: $app->make(GitHubClientContract::class),
                files: $app->make(Filesystem::class),
                tempPath: config('modular.github.temp_path', storage_path('modular/temp')),
            );
        });

        $this->app->singleton(ModuleBackup::class, function ($app) {
            return new ModuleBackup(
                files: $app->make(Filesystem::class),
                backupPath: config('modular.github.backup_path', storage_path('modular/backups')),
            );
        });

        $this->app->singleton(ModuleInstaller::class, function ($app) {
            return new ModuleInstaller(
                github: $app->make(GitHubClientContract::class),
                files: $app->make(Filesystem::class),
                pathScanner: $app->make(PathScanner::class),
                archiveHandler: $app->make(ArchiveHandler::class),
                backup: $app->make(ModuleBackup::class),
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
                logger()->error('[Modular] Failed to register module providers: '.$e->getMessage());
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
            ModuleLoader::class,
            StorageManager::class,
            ModuleStorageContract::class,
            GitHubClient::class,
            GitHubClientContract::class,
            ArchiveHandler::class,
            ModuleBackup::class,
            ModuleInstaller::class,
        ];
    }
}
