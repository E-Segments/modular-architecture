<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Cache;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Filesystem\Filesystem;

/**
 * Bridge Cache.
 *
 * Caches discovered bridge components for production performance.
 * Supports both file-based and cache-driver based storage.
 */
class BridgeCache
{
    protected const CACHE_KEY = 'modular:bridges:discovered';

    protected const FILE_NAME = 'bridges.php';

    public function __construct(
        protected readonly CacheRepository $cache,
        protected readonly Filesystem $files,
        protected readonly string $cachePath,
        protected readonly int $ttl = 86400,
        protected readonly bool $enabled = true,
        protected readonly string $driver = 'file',
    ) {}

    /**
     * Check if caching is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get cached bridge data.
     *
     * @return array<string, array<string, mixed>>|null
     */
    public function get(): ?array
    {
        if (! $this->enabled) {
            return null;
        }

        if ($this->driver === 'file') {
            return $this->getFromFile();
        }

        return $this->cache->get(self::CACHE_KEY);
    }

    /**
     * Store bridge data in cache.
     *
     * @param  array<string, array<string, mixed>>  $data
     */
    public function put(array $data): void
    {
        if (! $this->enabled) {
            return;
        }

        if ($this->driver === 'file') {
            $this->putToFile($data);

            return;
        }

        $this->cache->put(self::CACHE_KEY, $data, $this->ttl);
    }

    /**
     * Check if cache exists.
     */
    public function has(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if ($this->driver === 'file') {
            return $this->files->exists($this->getCacheFilePath());
        }

        return $this->cache->has(self::CACHE_KEY);
    }

    /**
     * Clear the bridge cache.
     */
    public function clear(): void
    {
        if ($this->driver === 'file') {
            $this->files->delete($this->getCacheFilePath());

            return;
        }

        $this->cache->forget(self::CACHE_KEY);
    }

    /**
     * Get data for a specific bridge.
     *
     * @return array<string, mixed>|null
     */
    public function getBridge(string $bridgeName): ?array
    {
        $data = $this->get();

        return $data[$bridgeName] ?? null;
    }

    /**
     * Store data for a specific bridge.
     *
     * @param  array<string, mixed>  $bridgeData
     */
    public function putBridge(string $bridgeName, array $bridgeData): void
    {
        $data = $this->get() ?? [];
        $data[$bridgeName] = $bridgeData;
        $this->put($data);
    }

    /**
     * Get the cache file path.
     */
    protected function getCacheFilePath(): string
    {
        return rtrim($this->cachePath, '/').'/'.self::FILE_NAME;
    }

    /**
     * Get data from file cache.
     *
     * @return array<string, array<string, mixed>>|null
     */
    protected function getFromFile(): ?array
    {
        $path = $this->getCacheFilePath();

        if (! $this->files->exists($path)) {
            return null;
        }

        try {
            return require $path;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Store data to file cache.
     *
     * @param  array<string, array<string, mixed>>  $data
     */
    protected function putToFile(array $data): void
    {
        $path = $this->getCacheFilePath();
        $directory = dirname($path);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $content = '<?php return '.var_export($data, true).';';

        $this->files->put($path, $content);
    }

    /**
     * Warm the cache by storing current bridge data.
     *
     * @param  array<string, array<string, mixed>>  $bridgeData
     */
    public function warm(array $bridgeData): void
    {
        $this->put($bridgeData);
    }

    /**
     * Get cache statistics.
     *
     * @return array{enabled: bool, driver: string, has_cache: bool, path: string|null}
     */
    public function stats(): array
    {
        return [
            'enabled' => $this->enabled,
            'driver' => $this->driver,
            'has_cache' => $this->has(),
            'path' => $this->driver === 'file' ? $this->getCacheFilePath() : null,
        ];
    }
}
