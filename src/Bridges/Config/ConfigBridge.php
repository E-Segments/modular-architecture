<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Config;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Config Bridge.
 *
 * Auto-discovers and merges configuration files from enabled modules.
 *
 * Discovery paths (in priority order):
 * - config/{alias}.php (module-specific config)
 * - config/config.php (default config)
 * - config/{environment}.php (environment-specific overrides)
 *
 * Features:
 * - Deep array merging (module configs extend base Laravel config)
 * - Environment-specific config overrides
 * - Namespace isolation (configs merged under module alias)
 * - Config caching for performance
 *
 * Usage:
 * - config('products.feature_enabled')
 * - config('orders.max_items_per_order')
 */
class ConfigBridge extends AbstractBridge
{
    /**
     * Discovered config files per module.
     *
     * @var array<string, array<string, string>>
     */
    protected array $configFiles = [];

    /**
     * Merged config values.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $mergedConfigs = [];

    public function name(): string
    {
        return 'config';
    }

    protected function configKey(): string
    {
        return 'config';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Config\\Repository';
    }

    protected function componentPath(): string
    {
        return 'config';
    }

    public function registerModule(Module $module): void
    {
        $configPath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($configPath)) {
            return;
        }

        $files = $this->app->make(Filesystem::class);
        $alias = $this->getConfigNamespace($module);
        $discovered = $this->discoverConfigs($files, $configPath, $alias);

        if (empty($discovered)) {
            return;
        }

        $this->registered->put($module->getName(), [
            'path' => $configPath,
            'namespace' => $alias,
            'files' => $discovered,
        ]);

        $this->configFiles[$alias] = $discovered;

        $this->log("Discovered {$module->getName()} configs", [
            'files' => count($discovered),
            'namespace' => $alias,
        ]);
    }

    public function boot(): void
    {
        $config = $this->app->make('config');
        $env = $this->app->environment();

        foreach ($this->configFiles as $namespace => $files) {
            // Load base config first
            if (isset($files['main'])) {
                $baseConfig = $this->loadConfig($files['main']);
                $this->mergeConfig($config, $namespace, $baseConfig);
            }

            // Load alias-specific config (overrides main)
            if (isset($files['alias'])) {
                $aliasConfig = $this->loadConfig($files['alias']);
                $this->mergeConfig($config, $namespace, $aliasConfig);
            }

            // Load environment-specific config (highest priority)
            $envKey = "env_{$env}";
            if (isset($files[$envKey])) {
                $envConfig = $this->loadConfig($files[$envKey]);
                $this->mergeConfig($config, $namespace, $envConfig);
            }

            // Store the final merged config
            $this->mergedConfigs[$namespace] = $config->get($namespace, []);
        }

        $this->log('Merged module configs', [
            'namespaces' => count($this->configFiles),
        ]);
    }

    /**
     * Discover configuration files in a module.
     *
     * @return array<string, string>
     */
    protected function discoverConfigs(Filesystem $files, string $configPath, string $alias): array
    {
        $discovered = [];
        $env = $this->app->environment();

        // Check for main config.php
        $mainConfig = $configPath.'/config.php';
        if ($files->exists($mainConfig)) {
            $discovered['main'] = $mainConfig;
        }

        // Check for alias-specific config (e.g., products.php)
        $aliasConfig = $configPath.'/'.$alias.'.php';
        if ($files->exists($aliasConfig)) {
            $discovered['alias'] = $aliasConfig;
        }

        // Check for environment-specific configs
        $envConfig = $configPath.'/'.$env.'.php';
        if ($files->exists($envConfig)) {
            $discovered["env_{$env}"] = $envConfig;
        }

        // Also check for common environment files
        foreach (['local', 'production', 'testing', 'staging'] as $envName) {
            $envFile = $configPath.'/'.$envName.'.php';
            if ($files->exists($envFile) && ! isset($discovered["env_{$envName}"])) {
                $discovered["env_{$envName}"] = $envFile;
            }
        }

        return $discovered;
    }

    /**
     * Get the config namespace for a module.
     */
    protected function getConfigNamespace(Module $module): string
    {
        return $module->getAlias() ?: Str::snake($module->getName());
    }

    /**
     * Load a config file.
     *
     * @return array<string, mixed>
     */
    protected function loadConfig(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $config = require $path;

        return is_array($config) ? $config : [];
    }

    /**
     * Merge config values using deep array merge.
     *
     * @param  mixed  $config
     * @param  array<string, mixed>  $values
     */
    protected function mergeConfig($config, string $namespace, array $values): void
    {
        $existing = $config->get($namespace, []);

        // Deep merge: new values override existing, arrays are merged recursively
        $merged = $this->deepMerge($existing, $values);

        $config->set($namespace, $merged);
    }

    /**
     * Deep merge two arrays.
     *
     * Values from $array2 override values from $array1.
     * For nested arrays, merging is recursive.
     *
     * @param  array<string, mixed>  $array1
     * @param  array<string, mixed>  $array2
     * @return array<string, mixed>
     */
    protected function deepMerge(array $array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                // Recursively merge nested arrays
                $array1[$key] = $this->deepMerge($array1[$key], $value);
            } else {
                // Override value
                $array1[$key] = $value;
            }
        }

        return $array1;
    }

    /**
     * Get all discovered config files.
     *
     * @return array<string, array<string, string>>
     */
    public function getConfigFiles(): array
    {
        return $this->configFiles;
    }

    /**
     * Get merged config for a specific namespace.
     *
     * @return array<string, mixed>
     */
    public function getMergedConfig(string $namespace): array
    {
        return $this->mergedConfigs[$namespace] ?? [];
    }

    /**
     * Get all merged configs.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getMergedConfigs(): array
    {
        return $this->mergedConfigs;
    }

    /**
     * Check if a module has config registered.
     */
    public function hasConfig(string $namespace): bool
    {
        return isset($this->configFiles[$namespace]);
    }

    /**
     * Load from cache with additional config data.
     *
     * @param  array<string, mixed>  $cachedData
     */
    public function loadFromCache(array $cachedData): void
    {
        parent::loadFromCache($cachedData);

        $this->configFiles = $cachedData['config_files'] ?? [];
        $this->mergedConfigs = $cachedData['merged_configs'] ?? [];
    }

    /**
     * Get cache data including config-specific data.
     *
     * @return array<string, mixed>
     */
    public function getCacheData(): array
    {
        return array_merge(parent::getCacheData(), [
            'config_files' => $this->configFiles,
            'merged_configs' => $this->mergedConfigs,
        ]);
    }

    /**
     * Get detailed status for inspection.
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        return array_merge(parent::getStatus(), [
            'config_namespaces' => array_keys($this->configFiles),
            'total_config_files' => array_sum(array_map('count', $this->configFiles)),
        ]);
    }
}
