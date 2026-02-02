<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Discovery;

use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

class PathScanner
{
    /**
     * @param  array<string>  $paths
     * @param  array<string>  $exclude
     */
    public function __construct(
        protected array $paths = [],
        protected array $exclude = ['vendor', 'node_modules', 'tests'],
    ) {}

    /**
     * @param  array<string>  $paths
     */
    public function setPaths(array $paths): self
    {
        $this->paths = $paths;

        return $this;
    }

    public function addPath(string $path): self
    {
        if (! in_array($path, $this->paths, true)) {
            $this->paths[] = $path;
        }

        return $this;
    }

    /**
     * @param  array<string>  $exclude
     */
    public function setExclude(array $exclude): self
    {
        $this->exclude = $exclude;

        return $this;
    }

    /**
     * Scan all configured paths for module directories.
     *
     * @return Collection<int, string>
     */
    public function scan(): Collection
    {
        $modulePaths = collect();

        foreach ($this->paths as $basePath) {
            if (! is_dir($basePath)) {
                continue;
            }

            $modulePaths = $modulePaths->merge($this->scanPath($basePath));
        }

        return $modulePaths->unique()->values();
    }

    /**
     * Scan a single path for module directories.
     *
     * @return Collection<int, string>
     */
    protected function scanPath(string $basePath): Collection
    {
        $modules = collect();

        try {
            $finder = Finder::create()
                ->in($basePath)
                ->directories()
                ->depth('== 0')
                ->exclude($this->exclude);

            foreach ($finder as $directory) {
                $modulePath = $directory->getPathname();
                $manifestPath = $modulePath . '/module.json';

                if (file_exists($manifestPath)) {
                    $modules->push($modulePath);
                }
            }
        } catch (\Exception) {
            // Skip inaccessible directories
        }

        return $modules;
    }

    /**
     * Check if a specific module exists in any of the configured paths.
     */
    public function findModule(string $name): ?string
    {
        foreach ($this->paths as $basePath) {
            $modulePath = rtrim($basePath, '/') . '/' . $name;
            $manifestPath = $modulePath . '/module.json';

            if (file_exists($manifestPath)) {
                return $modulePath;
            }
        }

        return null;
    }

    /**
     * Get all configured paths.
     *
     * @return array<string>
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Get the first valid path for creating new modules.
     */
    public function getDefaultPath(): ?string
    {
        foreach ($this->paths as $path) {
            if (is_dir($path) && is_writable($path)) {
                return $path;
            }
        }

        return $this->paths[0] ?? null;
    }
}
