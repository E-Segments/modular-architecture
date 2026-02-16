<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Link;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Filesystem\Filesystem;

/**
 * Link Bridge.
 *
 * Auto-discovers and loads link definition files from enabled modules.
 * Link definitions are PHP files that call Links::define() to register
 * cross-module relationships, macros, and Filament integrations.
 *
 * Example:
 *   Modules/Products/app/Links/ProductBrandLink.php
 *   Contains: Links::define('ProductBrandLink')->requires('Products', 'Brands')...
 *
 * This bridge integrates with the Core module's LinkRegistry and LinkBootstrapper.
 * It discovers link files during module registration and defers actual loading
 * to the LinkBootstrapper which handles conditional activation based on
 * module availability.
 */
class LinkBridge extends AbstractBridge
{
    /**
     * Discovered link files pending loading.
     *
     * @var array<string, string>
     */
    protected array $linkFiles = [];

    public function name(): string
    {
        return 'link';
    }

    protected function configKey(): string
    {
        return 'link';
    }

    protected function requiredClass(): string
    {
        // The LinkRegistry class from Core module
        return 'Modules\\Core\\Links\\LinkRegistry';
    }

    protected function componentPath(): string
    {
        return 'app/Links';
    }

    /**
     * Register a module with the link bridge.
     *
     * Discovers link definition files in the module's Links directory.
     * Files are not loaded here - they're tracked for the LinkBootstrapper.
     */
    public function registerModule(Module $module): void
    {
        $linksPath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($linksPath)) {
            return;
        }

        $discovered = $this->discoverLinkFiles($linksPath);

        if (empty($discovered)) {
            return;
        }

        $this->registered->put($module->getName(), [
            'path' => $linksPath,
            'files' => $discovered,
        ]);

        foreach ($discovered as $name => $path) {
            $this->linkFiles[$name] = $path;
        }

        $this->log("Discovered {$module->getName()} link definitions", [
            'count' => count($discovered),
            'links' => array_keys($discovered),
        ]);
    }

    /**
     * Boot the link bridge.
     *
     * Note: Actual link loading is handled by the Core module's LinkBootstrapper.
     * This bridge primarily provides discovery and tracking for integration
     * with the modular architecture system.
     */
    public function boot(): void
    {
        // The LinkBootstrapper in Core module handles the actual loading
        // and booting of link definitions. This bridge integrates with
        // the modular architecture's discovery system.

        $this->log('Link bridge booted', [
            'discovered_files' => count($this->linkFiles),
            'modules' => $this->registered->keys()->toArray(),
        ]);
    }

    /**
     * Discover link definition files in a directory.
     *
     * @return array<string, string> Map of link name => file path
     */
    protected function discoverLinkFiles(string $path): array
    {
        $files = $this->app->make(Filesystem::class);
        $links = [];

        foreach ($files->files($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Use the filename (without extension) as the link name
            $linkName = $file->getBasename('.php');
            $links[$linkName] = $file->getPathname();
        }

        return $links;
    }

    /**
     * Get all discovered link files.
     *
     * @return array<string, string> Map of link name => file path
     */
    public function getLinkFiles(): array
    {
        return $this->linkFiles;
    }

    /**
     * Get link files for a specific module.
     *
     * @return array<string, string>|null
     */
    public function getModuleLinkFiles(string $moduleName): ?array
    {
        $moduleData = $this->registered->get($moduleName);

        if ($moduleData === null) {
            return null;
        }

        return $moduleData['files'] ?? null;
    }

    /**
     * Check if a specific link file was discovered.
     */
    public function hasLinkFile(string $linkName): bool
    {
        return isset($this->linkFiles[$linkName]);
    }

    /**
     * Get the path to a specific link file.
     */
    public function getLinkFilePath(string $linkName): ?string
    {
        return $this->linkFiles[$linkName] ?? null;
    }

    /**
     * Load bridge state from cached data.
     *
     * @param  array<string, mixed>  $cachedData
     */
    public function loadFromCache(array $cachedData): void
    {
        parent::loadFromCache($cachedData);
        $this->linkFiles = $cachedData['link_files'] ?? [];
    }

    /**
     * Get data to be cached.
     *
     * @return array<string, mixed>
     */
    public function getCacheData(): array
    {
        return array_merge(parent::getCacheData(), [
            'link_files' => $this->linkFiles,
        ]);
    }

    /**
     * Get summary statistics.
     *
     * @return array{modules: int, files: int, links: array<string>}
     */
    public function getSummary(): array
    {
        return [
            'modules' => $this->registered->count(),
            'files' => count($this->linkFiles),
            'links' => array_keys($this->linkFiles),
        ];
    }
}
