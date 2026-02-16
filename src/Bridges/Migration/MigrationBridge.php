<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Migration;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Filesystem\Filesystem;

/**
 * Migration Bridge.
 *
 * Auto-discovers and loads migrations from enabled modules.
 *
 * Discovery path: database/migrations/*.php
 *
 * Migrations are loaded with Laravel's loadMigrationsFrom() method,
 * making them available to `php artisan migrate`.
 */
class MigrationBridge extends AbstractBridge
{
    /**
     * Discovered migration paths.
     *
     * @var array<string, string>
     */
    protected array $migrationPaths = [];

    public function name(): string
    {
        return 'migration';
    }

    protected function configKey(): string
    {
        return 'migrations';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Database\\Migrations\\Migrator';
    }

    protected function componentPath(): string
    {
        return 'database/migrations';
    }

    public function registerModule(Module $module): void
    {
        $migrationsPath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($migrationsPath)) {
            return;
        }

        $files = $this->app->make(Filesystem::class);
        $migrations = $files->glob($migrationsPath.'/*.php');

        if (empty($migrations)) {
            return;
        }

        $this->registered->put($module->getName(), [
            'path' => $migrationsPath,
            'count' => count($migrations),
            'files' => array_map('basename', $migrations),
        ]);

        $this->migrationPaths[$module->getName()] = $migrationsPath;

        $this->log("Discovered {$module->getName()} migrations", [
            'count' => count($migrations),
        ]);
    }

    public function boot(): void
    {
        if (empty($this->migrationPaths)) {
            return;
        }

        foreach ($this->migrationPaths as $moduleName => $path) {
            $this->loadMigrationsFrom($path);
        }

        $this->log('Registered migration paths', [
            'count' => count($this->migrationPaths),
        ]);
    }

    /**
     * Load migrations from a path.
     */
    protected function loadMigrationsFrom(string $path): void
    {
        $this->app->afterResolving('migrator', function ($migrator) use ($path) {
            $migrator->path($path);
        });
    }

    /**
     * Get all discovered migration paths.
     *
     * @return array<string, string>
     */
    public function getMigrationPaths(): array
    {
        return $this->migrationPaths;
    }

    /**
     * Get total migration count across all modules.
     */
    public function getTotalMigrationCount(): int
    {
        return $this->registered->sum(fn ($data) => $data['count']);
    }
}
