<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Links;

use Illuminate\Contracts\Foundation\Application;

/**
 * Bootstrapper for discovering and loading link definitions.
 *
 * The LinkBootstrapper scans domain modules for link definition files
 * in their `app/Links/` directories and registers them with the LinkRegistry.
 *
 * Link definition files should be named like `ProductBrandLink.php` and
 * should call `Links::define()` to register themselves.
 *
 * Example link definition file:
 * ```php
 * // Modules/Products/app/Links/ProductBrandLink.php
 * <?php
 * use Esegments\ModularArchitecture\Facades\Links;
 * use Modules\Products\Models\Product;
 * use Modules\Brands\Models\Brand;
 *
 * Links::define('ProductBrandLink')
 *     ->requires('Products', 'Brands')
 *     ->belongsTo(Product::class, 'brand', Brand::class, 'brand_id');
 * ```
 */
class LinkBootstrapper
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The link registry.
     */
    protected LinkRegistry $registry;

    /**
     * The base modules path.
     */
    protected string $modulesPath;

    /**
     * Discovered link files.
     *
     * @var array<string>
     */
    protected array $discoveredFiles = [];

    /**
     * Whether discovery has been run.
     */
    protected bool $discovered = false;

    /**
     * Create a new bootstrapper instance.
     *
     * @param  string|null  $modulesPath  Optional modules path (defaults to base_path('Modules'))
     */
    public function __construct(Application $app, LinkRegistry $registry, ?string $modulesPath = null)
    {
        $this->app = $app;
        $this->registry = $registry;
        $this->modulesPath = $modulesPath ?? base_path('Modules');
    }

    /**
     * Set the modules path.
     */
    public function setModulesPath(string $path): self
    {
        $this->modulesPath = $path;

        return $this;
    }

    /**
     * Get the modules path.
     */
    public function getModulesPath(): string
    {
        return $this->modulesPath;
    }

    /**
     * Discover link definition files from all modules.
     *
     * Scans Modules/{Module}/app/Links/{Link}.php for link definition files.
     *
     * @return array<string> List of discovered file paths
     */
    public function discover(): array
    {
        if ($this->discovered) {
            return $this->discoveredFiles;
        }

        $this->discoveredFiles = [];

        if (! is_dir($this->modulesPath)) {
            $this->discovered = true;

            return $this->discoveredFiles;
        }

        $pattern = $this->modulesPath.'/*/app/Links/*.php';
        $files = glob($pattern);

        if ($files === false) {
            $this->discovered = true;

            return $this->discoveredFiles;
        }

        $this->discoveredFiles = $files;
        $this->discovered = true;

        return $this->discoveredFiles;
    }

    /**
     * Load all discovered link definition files.
     *
     * Each file is included, which should call Links::define()
     * to register the link with the registry.
     *
     * Note: We use `include` instead of `require_once` because link files
     * only contain Links::define() calls (no class definitions) and need
     * to be re-executed for each fresh application instance (e.g., in testing).
     */
    public function load(): void
    {
        $files = $this->discover();

        foreach ($files as $file) {
            try {
                include $file;
            } catch (\Throwable $e) {
                if ($this->app->hasDebugModeEnabled()) {
                    if (function_exists('logger')) {
                        logger()->error("[LinkBootstrapper] Failed to load link file: {$file}", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Boot the link system.
     *
     * Discovers link files, loads them, and boots the registry.
     */
    public function boot(): void
    {
        $this->load();
        $this->registry->boot();
    }

    /**
     * Get the list of discovered files.
     *
     * @return array<string>
     */
    public function getDiscoveredFiles(): array
    {
        return $this->discoveredFiles;
    }

    /**
     * Check if discovery has been run.
     */
    public function hasDiscovered(): bool
    {
        return $this->discovered;
    }

    /**
     * Reset the bootstrapper state.
     *
     * Primarily useful for testing.
     */
    public function reset(): void
    {
        $this->discoveredFiles = [];
        $this->discovered = false;
    }

    /**
     * Get discovery statistics.
     *
     * @return array{modules_path: string, discovered_files: int, registry_summary: array}
     */
    public function statistics(): array
    {
        return [
            'modules_path' => $this->modulesPath,
            'discovered_files' => count($this->discoveredFiles),
            'registry_summary' => $this->registry->summary(),
        ];
    }

    /**
     * Discover links for a specific module.
     *
     * @param  string  $moduleName  The module name
     * @return array<string> List of discovered file paths for this module
     */
    public function discoverForModule(string $moduleName): array
    {
        $linksPath = $this->modulesPath.'/'.$moduleName.'/app/Links';

        if (! is_dir($linksPath)) {
            return [];
        }

        $files = glob($linksPath.'/*.php');

        return $files !== false ? $files : [];
    }

    /**
     * Load links for a specific module.
     *
     * @param  string  $moduleName  The module name
     */
    public function loadForModule(string $moduleName): void
    {
        $files = $this->discoverForModule($moduleName);

        foreach ($files as $file) {
            try {
                include $file;
            } catch (\Throwable $e) {
                if ($this->app->hasDebugModeEnabled()) {
                    if (function_exists('logger')) {
                        logger()->error("[LinkBootstrapper] Failed to load link file: {$file}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Get module names that have link definitions.
     *
     * @return array<string>
     */
    public function getModulesWithLinks(): array
    {
        $modules = [];

        if (! is_dir($this->modulesPath)) {
            return $modules;
        }

        $dirs = glob($this->modulesPath.'/*', GLOB_ONLYDIR);

        if ($dirs === false) {
            return $modules;
        }

        foreach ($dirs as $dir) {
            $linksPath = $dir.'/app/Links';

            if (is_dir($linksPath)) {
                $files = glob($linksPath.'/*.php');

                if ($files !== false && count($files) > 0) {
                    $modules[] = basename($dir);
                }
            }
        }

        return $modules;
    }
}
