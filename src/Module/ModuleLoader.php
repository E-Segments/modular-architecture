<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Module;

use Esegments\ModularArchitecture\Exceptions\InvalidManifestException;
use Illuminate\Filesystem\Filesystem;

/**
 * Handles loading modules from filesystem with proper dependency injection.
 */
class ModuleLoader
{
    public function __construct(
        protected readonly Filesystem $files,
    ) {}

    /**
     * Load a module from a path.
     *
     * @throws InvalidManifestException
     */
    public function load(string $path): Module
    {
        $manifestPath = rtrim($path, '/').'/module.json';

        if (! $this->files->exists($manifestPath)) {
            throw InvalidManifestException::notFound($manifestPath);
        }

        $content = $this->files->get($manifestPath);
        $manifest = ModuleManifest::fromJson($content);

        return new Module($manifest, $path);
    }

    /**
     * Check if a valid module exists at the given path.
     */
    public function exists(string $path): bool
    {
        $manifestPath = rtrim($path, '/').'/module.json';

        return $this->files->exists($manifestPath);
    }
}
