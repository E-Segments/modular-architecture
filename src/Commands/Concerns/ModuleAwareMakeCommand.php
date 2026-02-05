<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands\Concerns;

use Esegments\ModularArchitecture\Discovery\PathScanner;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

/**
 * Trait for making Laravel make:* commands module-aware.
 *
 * Adds --module option and modifies the generated file path
 * to be within the specified module's directory structure.
 */
trait ModuleAwareMakeCommand
{
    /**
     * Get the module path from PathScanner.
     */
    protected function getModulePath(string $moduleName): ?string
    {
        $scanner = app(PathScanner::class);

        return $scanner->findModule($moduleName);
    }

    /**
     * Get the root namespace for a module.
     */
    protected function getModuleNamespace(string $moduleName): string
    {
        return "Modules\\{$moduleName}";
    }

    /**
     * Get the default namespace for the class within a module.
     */
    protected function getModuleDefaultNamespace(string $moduleName, string $type): string
    {
        $baseNamespace = $this->getModuleNamespace($moduleName);

        return match ($type) {
            'model' => "{$baseNamespace}\\Models",
            'controller' => "{$baseNamespace}\\Http\\Controllers",
            'policy' => "{$baseNamespace}\\Policies",
            'event' => "{$baseNamespace}\\Events",
            'listener' => "{$baseNamespace}\\Listeners",
            'job' => "{$baseNamespace}\\Jobs",
            'notification' => "{$baseNamespace}\\Notifications",
            'mail' => "{$baseNamespace}\\Mail",
            'rule' => "{$baseNamespace}\\Rules",
            'request' => "{$baseNamespace}\\Http\\Requests",
            'resource' => "{$baseNamespace}\\Http\\Resources",
            'exception' => "{$baseNamespace}\\Exceptions",
            'middleware' => "{$baseNamespace}\\Http\\Middleware",
            'observer' => "{$baseNamespace}\\Observers",
            'cast' => "{$baseNamespace}\\Casts",
            'provider' => "{$baseNamespace}\\Providers",
            'command' => "{$baseNamespace}\\Console\\Commands",
            'channel' => "{$baseNamespace}\\Broadcasting",
            'scope' => "{$baseNamespace}\\Models\\Scopes",
            default => $baseNamespace,
        };
    }

    /**
     * Get the path for generated files within a module.
     */
    protected function getModuleFilePath(string $moduleName, string $type): string
    {
        $modulePath = $this->getModulePath($moduleName);

        if (! $modulePath) {
            throw new \RuntimeException("Module [{$moduleName}] not found.");
        }

        return match ($type) {
            'model' => "{$modulePath}/app/Models",
            'controller' => "{$modulePath}/app/Http/Controllers",
            'migration' => "{$modulePath}/database/migrations",
            'factory' => "{$modulePath}/database/factories",
            'seeder' => "{$modulePath}/database/seeders",
            'policy' => "{$modulePath}/app/Policies",
            'event' => "{$modulePath}/app/Events",
            'listener' => "{$modulePath}/app/Listeners",
            'job' => "{$modulePath}/app/Jobs",
            'notification' => "{$modulePath}/app/Notifications",
            'mail' => "{$modulePath}/app/Mail",
            'rule' => "{$modulePath}/app/Rules",
            'request' => "{$modulePath}/app/Http/Requests",
            'resource' => "{$modulePath}/app/Http/Resources",
            'test' => "{$modulePath}/tests",
            'test-unit' => "{$modulePath}/tests/Unit",
            'test-feature' => "{$modulePath}/tests/Feature",
            'exception' => "{$modulePath}/app/Exceptions",
            'middleware' => "{$modulePath}/app/Http/Middleware",
            'observer' => "{$modulePath}/app/Observers",
            'cast' => "{$modulePath}/app/Casts",
            'provider' => "{$modulePath}/app/Providers",
            'command' => "{$modulePath}/app/Console/Commands",
            'channel' => "{$modulePath}/app/Broadcasting",
            'scope' => "{$modulePath}/app/Models/Scopes",
            default => "{$modulePath}/app",
        };
    }

    /**
     * Get the module name from the --module option.
     */
    protected function getModuleName(): ?string
    {
        return $this->option('module');
    }

    /**
     * Check if a module is specified.
     */
    protected function hasModule(): bool
    {
        return ! empty($this->option('module'));
    }

    /**
     * Validate that the specified module exists.
     */
    protected function validateModule(): bool
    {
        $moduleName = $this->getModuleName();

        if (! $moduleName) {
            return true;
        }

        $modulePath = $this->getModulePath($moduleName);

        if (! $modulePath) {
            $this->components->error("Module [{$moduleName}] does not exist.");

            return false;
        }

        return true;
    }

    /**
     * Add the --module option to the command signature.
     */
    protected function getModuleOption(): InputOption
    {
        return new InputOption(
            'module',
            'm',
            InputOption::VALUE_OPTIONAL,
            'The module to create the class in'
        );
    }

    /**
     * Transform a name to include the module namespace prefix if needed.
     */
    protected function qualifyModuleClass(string $name, string $type): string
    {
        $moduleName = $this->getModuleName();

        if (! $moduleName) {
            return $name;
        }

        // If the name already has a namespace, preserve it
        if (Str::contains($name, '/') || Str::contains($name, '\\')) {
            return $name;
        }

        return $name;
    }

    /**
     * Get the destination path for a module-aware class.
     */
    protected function getModuleDestinationPath(string $name, string $type): string
    {
        $moduleName = $this->getModuleName();

        if (! $moduleName) {
            throw new \RuntimeException('No module specified.');
        }

        $basePath = $this->getModuleFilePath($moduleName, $type);

        // Handle nested classes (e.g., Admin/UserController)
        $name = str_replace('\\', '/', $name);

        return "{$basePath}/{$name}.php";
    }
}
