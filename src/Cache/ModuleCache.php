<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Cache;

use Esegments\ModularArchitecture\Module\ModuleCollection;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Filesystem\Filesystem;

class ModuleCache
{
    /**
     * Static cache for Octane compatibility.
     */
    protected static ?ModuleCollection $staticCache = null;

    protected const CACHE_KEY = 'modular:modules';

    protected const MANIFEST_FILE = 'modular-manifest.php';

    public function __construct(
        protected readonly CacheRepository $cache,
        protected readonly Filesystem $files,
        protected readonly string $cachePath,
        protected readonly int $ttl = 86400,
        protected readonly bool $enabled = true,
    ) {}

    /**
     * Get cached modules.
     */
    public function get(): ?ModuleCollection
    {
        if (! $this->enabled) {
            return null;
        }

        // Check static cache first (Octane-compatible)
        if (self::$staticCache !== null) {
            return self::$staticCache;
        }

        // Try file manifest cache
        $manifestPath = $this->getManifestPath();
        if ($this->files->exists($manifestPath)) {
            try {
                $data = require $manifestPath;
                if (is_array($data)) {
                    $collection = $this->hydrateFromCache($data);
                    self::$staticCache = $collection;

                    return $collection;
                }
            } catch (\Exception) {
                // Fall through to regular cache
            }
        }

        // Try regular cache
        $cached = $this->cache->get(self::CACHE_KEY);
        if ($cached instanceof ModuleCollection) {
            self::$staticCache = $cached;

            return $cached;
        }

        return null;
    }

    /**
     * Store modules in cache.
     */
    public function put(ModuleCollection $modules): void
    {
        if (! $this->enabled) {
            return;
        }

        // Update static cache
        self::$staticCache = $modules;

        // Store in regular cache
        $this->cache->put(self::CACHE_KEY, $modules, $this->ttl);
    }

    /**
     * Build optimized manifest file for production.
     */
    public function buildManifest(ModuleCollection $modules): void
    {
        $data = $this->dehydrateForCache($modules);

        $content = '<?php return '.var_export($data, true).';';

        $this->files->ensureDirectoryExists(dirname($this->getManifestPath()));
        $this->files->put($this->getManifestPath(), $content);
    }

    /**
     * Clear all caches.
     */
    public function clear(): void
    {
        self::$staticCache = null;
        $this->cache->forget(self::CACHE_KEY);

        $manifestPath = $this->getManifestPath();
        if ($this->files->exists($manifestPath)) {
            $this->files->delete($manifestPath);
        }
    }

    /**
     * Check if cache exists.
     */
    public function exists(): bool
    {
        if (self::$staticCache !== null) {
            return true;
        }

        if ($this->files->exists($this->getManifestPath())) {
            return true;
        }

        return $this->cache->has(self::CACHE_KEY);
    }

    /**
     * Get manifest file path.
     */
    protected function getManifestPath(): string
    {
        return $this->cachePath.'/'.self::MANIFEST_FILE;
    }

    /**
     * Dehydrate modules for storage.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function dehydrateForCache(ModuleCollection $modules): array
    {
        $data = [];

        foreach ($modules as $module) {
            $data[$module->getName()] = [
                'path' => $module->getPath(),
                'enabled' => $module->isEnabled(),
                'protected' => $module->isProtected(),
                'manifest' => $module->getManifest(),
            ];
        }

        return $data;
    }

    /**
     * Hydrate modules from cache data.
     *
     * @param  array<string, array<string, mixed>>  $data
     */
    protected function hydrateFromCache(array $data): ModuleCollection
    {
        $collection = new ModuleCollection;

        foreach ($data as $name => $moduleData) {
            try {
                $module = \Esegments\ModularArchitecture\Module\Module::fromPath($moduleData['path']);

                if ($moduleData['enabled'] ?? true) {
                    $module->enable();
                } else {
                    $module->disable();
                }

                if ($moduleData['protected'] ?? false) {
                    $module->setProtected(true);
                }

                $collection->put($name, $module);
            } catch (\Exception) {
                // Skip invalid modules
            }
        }

        return $collection;
    }

    /**
     * Clear static cache (for testing/Octane).
     */
    public static function clearStatic(): void
    {
        self::$staticCache = null;
    }
}
