<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Asset;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Asset Bridge.
 *
 * Auto-discovers and publishes static assets from enabled modules.
 *
 * Discovery paths:
 * - resources/assets (development assets)
 * - public (pre-compiled public assets)
 *
 * Features:
 * - Asset publishing to public directory
 * - Symbolic linking for development
 * - Asset versioning support
 * - Module asset URL generation
 *
 * Usage:
 * - module_asset('products', 'css/styles.css')
 * - <link href="{{ module_asset('products', 'css/app.css') }}" />
 */
class AssetBridge extends AbstractBridge
{
    /**
     * Discovered asset paths.
     *
     * @var array<string, array{source: string, target: string}>
     */
    protected array $assetPaths = [];

    /**
     * Published assets.
     *
     * @var array<string, bool>
     */
    protected array $published = [];

    public function name(): string
    {
        return 'assets';
    }

    protected function configKey(): string
    {
        return 'assets';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Filesystem\\Filesystem';
    }

    protected function componentPath(): string
    {
        return 'public';
    }

    public function registerModule(Module $module): void
    {
        $publicPath = $module->getPath().'/'.$this->componentPath();
        $assetsPath = $module->getPath().'/resources/assets';

        $hasPublic = is_dir($publicPath);
        $hasAssets = is_dir($assetsPath);

        if (! $hasPublic && ! $hasAssets) {
            return;
        }

        $namespace = $this->getAssetNamespace($module);
        $targetPath = public_path("modules/{$namespace}");

        $discovered = [
            'source' => $hasPublic ? $publicPath : $assetsPath,
            'target' => $targetPath,
            'namespace' => $namespace,
            'type' => $hasPublic ? 'public' : 'assets',
        ];

        $this->registered->put($module->getName(), $discovered);
        $this->assetPaths[$module->getName()] = $discovered;

        $this->log("Discovered {$module->getName()} assets", [
            'type' => $discovered['type'],
            'source' => $discovered['source'],
        ]);
    }

    public function boot(): void
    {
        // Register helper function for asset URLs
        if (! function_exists('module_asset')) {
            $this->registerAssetHelper();
        }

        // Auto-publish if configured
        if (config('modular.bridges.assets.auto_publish', false)) {
            $this->publishAll();
        }

        // Create symlinks if configured
        if (config('modular.bridges.assets.symlinks', false)) {
            $this->createSymlinks();
        }

        $this->log('Asset bridge booted', [
            'modules' => count($this->assetPaths),
        ]);
    }

    /**
     * Register the module_asset helper function.
     */
    protected function registerAssetHelper(): void
    {
        $assetPaths = $this->assetPaths;

        if (! function_exists('module_asset')) {
            /**
             * Generate URL for a module asset.
             */
            function module_asset(string $module, string $path): string
            {
                $namespace = Str::kebab($module);

                return asset("modules/{$namespace}/{$path}");
            }
        }
    }

    /**
     * Publish all module assets.
     */
    public function publishAll(): void
    {
        $files = $this->app->make(Filesystem::class);

        foreach ($this->assetPaths as $moduleName => $config) {
            $this->publishModuleAssets($files, $moduleName, $config);
        }
    }

    /**
     * Publish assets for a specific module.
     *
     * @param  array{source: string, target: string, namespace: string, type: string}  $config
     */
    public function publishModuleAssets(Filesystem $files, string $moduleName, array $config): void
    {
        if (! is_dir($config['source'])) {
            return;
        }

        // Ensure target directory exists
        if (! is_dir($config['target'])) {
            $files->makeDirectory($config['target'], 0755, true);
        }

        // Copy files
        $files->copyDirectory($config['source'], $config['target']);

        $this->published[$moduleName] = true;

        $this->log("Published {$moduleName} assets", [
            'target' => $config['target'],
        ]);
    }

    /**
     * Create symbolic links for module assets.
     */
    public function createSymlinks(): void
    {
        $files = $this->app->make(Filesystem::class);

        foreach ($this->assetPaths as $moduleName => $config) {
            $this->createModuleSymlink($files, $moduleName, $config);
        }
    }

    /**
     * Create a symbolic link for a module's assets.
     *
     * @param  array{source: string, target: string, namespace: string, type: string}  $config
     */
    protected function createModuleSymlink(Filesystem $files, string $moduleName, array $config): void
    {
        // Remove existing target
        if (is_link($config['target'])) {
            $files->delete($config['target']);
        } elseif (is_dir($config['target'])) {
            $files->deleteDirectory($config['target']);
        }

        // Ensure parent directory exists
        $parentDir = dirname($config['target']);
        if (! is_dir($parentDir)) {
            $files->makeDirectory($parentDir, 0755, true);
        }

        // Create symlink
        if (function_exists('symlink')) {
            symlink($config['source'], $config['target']);
            $this->log("Created symlink for {$moduleName} assets");
        }
    }

    /**
     * Get the asset namespace for a module.
     */
    protected function getAssetNamespace(Module $module): string
    {
        return Str::kebab($module->getName());
    }

    /**
     * Get the URL for a module asset.
     */
    public function url(string $moduleName, string $path): string
    {
        $namespace = Str::kebab($moduleName);

        return asset("modules/{$namespace}/{$path}");
    }

    /**
     * Check if a module's assets are published.
     */
    public function isPublished(string $moduleName): bool
    {
        return $this->published[$moduleName] ?? false;
    }

    /**
     * Get all asset paths.
     *
     * @return array<string, array{source: string, target: string, namespace: string, type: string}>
     */
    public function getAssetPaths(): array
    {
        return $this->assetPaths;
    }

    /**
     * Load from cache.
     *
     * @param  array<string, mixed>  $cachedData
     */
    public function loadFromCache(array $cachedData): void
    {
        parent::loadFromCache($cachedData);

        $this->assetPaths = $cachedData['asset_paths'] ?? [];
        $this->published = $cachedData['published'] ?? [];
    }

    /**
     * Get cache data.
     *
     * @return array<string, mixed>
     */
    public function getCacheData(): array
    {
        return array_merge(parent::getCacheData(), [
            'asset_paths' => $this->assetPaths,
            'published' => $this->published,
        ]);
    }

    /**
     * Get detailed status.
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        return array_merge(parent::getStatus(), [
            'total_modules' => count($this->assetPaths),
            'published_count' => count(array_filter($this->published)),
        ]);
    }
}
