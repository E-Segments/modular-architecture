<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Health;

use Esegments\ModularArchitecture\Modular;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Resolver\DependencyResolver;
use Illuminate\Filesystem\Filesystem;

class ModuleHealthChecker
{
    public function __construct(
        protected readonly Filesystem $files,
        protected readonly DependencyResolver $resolver,
        protected readonly Modular $modular,
    ) {}

    /**
     * Check the health of a module.
     *
     * @return array<string, array{status: string, message: string, details?: array}>
     */
    public function check(Module $module): array
    {
        return [
            'manifest' => $this->checkManifest($module),
            'service_provider' => $this->checkServiceProvider($module),
            'dependencies' => $this->checkDependencies($module),
            'config' => $this->checkConfig($module),
            'migrations' => $this->checkMigrations($module),
        ];
    }

    /**
     * Check if the module manifest is valid.
     *
     * @return array{status: string, message: string}
     */
    protected function checkManifest(Module $module): array
    {
        $manifestPath = $module->getPath().'/module.json';

        if (! $this->files->exists($manifestPath)) {
            return [
                'status' => 'error',
                'message' => 'module.json not found',
            ];
        }

        $content = $this->files->get($manifestPath);
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'error',
                'message' => 'Invalid JSON in module.json: '.json_last_error_msg(),
            ];
        }

        $requiredFields = ['name', 'version'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (! isset($decoded[$field]) || empty($decoded[$field])) {
                $missingFields[] = $field;
            }
        }

        if (! empty($missingFields)) {
            return [
                'status' => 'warning',
                'message' => 'Missing required fields: '.implode(', ', $missingFields),
            ];
        }

        return [
            'status' => 'ok',
            'message' => 'Valid manifest',
        ];
    }

    /**
     * Check if the service provider exists and is valid.
     *
     * @return array{status: string, message: string}
     */
    protected function checkServiceProvider(Module $module): array
    {
        $providers = $module->getProviders();

        if (empty($providers)) {
            return [
                'status' => 'warning',
                'message' => 'No service providers defined',
            ];
        }

        $missing = [];
        $valid = 0;

        foreach ($providers as $provider) {
            if (class_exists($provider)) {
                $valid++;
            } else {
                $missing[] = $provider;
            }
        }

        if (! empty($missing)) {
            return [
                'status' => 'error',
                'message' => 'Provider class(es) not found: '.implode(', ', $missing),
            ];
        }

        return [
            'status' => 'ok',
            'message' => "{$valid} provider(s) loaded successfully",
        ];
    }

    /**
     * Check if all dependencies are satisfied.
     *
     * @return array{status: string, message: string, details?: array}
     */
    protected function checkDependencies(Module $module): array
    {
        $requires = $module->getRequires();

        if (empty($requires)) {
            return [
                'status' => 'ok',
                'message' => 'No dependencies',
            ];
        }

        $missing = [];

        foreach ($requires as $name => $constraint) {
            if (! $this->modular->exists($name)) {
                $missing[] = "{$name} ({$constraint})";
            }
        }

        if (! empty($missing)) {
            return [
                'status' => 'error',
                'message' => 'Missing dependencies',
                'details' => $missing,
            ];
        }

        return [
            'status' => 'ok',
            'message' => count($requires).' dependencies satisfied',
        ];
    }

    /**
     * Check if config file exists and is valid.
     *
     * @return array{status: string, message: string}
     */
    protected function checkConfig(Module $module): array
    {
        $configPath = $module->getConfigPath();

        if (! $this->files->isDirectory($configPath)) {
            return [
                'status' => 'info',
                'message' => 'No config directory',
            ];
        }

        $configFiles = $this->files->glob($configPath.'/*.php');

        if (empty($configFiles)) {
            return [
                'status' => 'info',
                'message' => 'No config files',
            ];
        }

        $errors = [];

        foreach ($configFiles as $file) {
            try {
                $config = require $file;
                if (! is_array($config)) {
                    $errors[] = basename($file).': must return an array';
                }
            } catch (\Throwable $e) {
                $errors[] = basename($file).': '.$e->getMessage();
            }
        }

        if (! empty($errors)) {
            return [
                'status' => 'error',
                'message' => 'Config errors: '.implode('; ', $errors),
            ];
        }

        return [
            'status' => 'ok',
            'message' => count($configFiles).' config file(s) valid',
        ];
    }

    /**
     * Check if migrations directory exists.
     *
     * @return array{status: string, message: string}
     */
    protected function checkMigrations(Module $module): array
    {
        $migrationsPath = $module->getDatabasePath('migrations');

        if (! $this->files->isDirectory($migrationsPath)) {
            return [
                'status' => 'info',
                'message' => 'No migrations directory',
            ];
        }

        $migrations = $this->files->glob($migrationsPath.'/*.php');
        $count = count($migrations);

        if ($count === 0) {
            return [
                'status' => 'info',
                'message' => 'No migration files',
            ];
        }

        return [
            'status' => 'ok',
            'message' => "{$count} migration file(s) found",
        ];
    }

    /**
     * Get overall health status from check results.
     */
    public function getOverallStatus(array $results): string
    {
        $hasError = false;
        $hasWarning = false;

        foreach ($results as $result) {
            if ($result['status'] === 'error') {
                $hasError = true;
            } elseif ($result['status'] === 'warning') {
                $hasWarning = true;
            }
        }

        if ($hasError) {
            return 'error';
        }

        if ($hasWarning) {
            return 'warning';
        }

        return 'ok';
    }
}
