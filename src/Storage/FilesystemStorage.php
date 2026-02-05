<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Storage;

use Esegments\ModularArchitecture\Contracts\ModuleStorageContract;
use Esegments\ModularArchitecture\Support\Json;
use Illuminate\Filesystem\Filesystem;

class FilesystemStorage implements ModuleStorageContract
{
    protected array $states = [];

    protected array $metadata = [];

    protected bool $statesLoaded = false;

    protected bool $metadataLoaded = false;

    public function __construct(
        protected readonly Filesystem $files,
        protected readonly string $statesFile,
        protected readonly string $metadataFile,
    ) {}

    public function getState(string $name): ?bool
    {
        $this->loadStates();

        return $this->states[$name] ?? null;
    }

    public function setState(string $name, bool $enabled): void
    {
        $this->loadStates();
        $this->states[$name] = $enabled;
        $this->saveStates();
    }

    public function getAllStates(): array
    {
        $this->loadStates();

        return $this->states;
    }

    public function removeState(string $name): void
    {
        $this->loadStates();
        unset($this->states[$name]);
        $this->saveStates();
    }

    public function getMetadata(string $name): ?array
    {
        $this->loadMetadata();

        return $this->metadata[$name] ?? null;
    }

    public function setMetadata(string $name, array $metadata): void
    {
        $this->loadMetadata();
        $this->metadata[$name] = array_merge(
            $this->metadata[$name] ?? [],
            $metadata,
            ['updated_at' => now()->toIso8601String()]
        );
        $this->saveMetadata();
    }

    public function getAllMetadata(): array
    {
        $this->loadMetadata();

        return $this->metadata;
    }

    public function removeMetadata(string $name): void
    {
        $this->loadMetadata();
        unset($this->metadata[$name]);
        $this->saveMetadata();
    }

    public function isAvailable(): bool
    {
        $statesDir = dirname($this->statesFile);

        if (! $this->files->isDirectory($statesDir)) {
            return $this->files->makeDirectory($statesDir, 0755, true);
        }

        return $this->files->isWritable($statesDir);
    }

    public function clear(): void
    {
        $this->states = [];
        $this->metadata = [];
        $this->statesLoaded = false;
        $this->metadataLoaded = false;

        if ($this->files->exists($this->statesFile)) {
            $this->files->delete($this->statesFile);
        }

        if ($this->files->exists($this->metadataFile)) {
            $this->files->delete($this->metadataFile);
        }
    }

    protected function loadStates(): void
    {
        if (! $this->statesLoaded) {
            $this->states = $this->loadFromFile($this->statesFile);
            $this->statesLoaded = true;
        }
    }

    protected function saveStates(): void
    {
        $this->saveToFile($this->statesFile, $this->states);
    }

    protected function loadMetadata(): void
    {
        if (! $this->metadataLoaded) {
            $this->metadata = $this->loadFromFile($this->metadataFile);
            $this->metadataLoaded = true;
        }
    }

    protected function saveMetadata(): void
    {
        $this->saveToFile($this->metadataFile, $this->metadata);
    }

    /**
     * Load array data from a JSON file.
     */
    protected function loadFromFile(string $path): array
    {
        if (! $this->files->exists($path)) {
            return [];
        }

        $data = Json::decode($this->files->get($path));

        return is_array($data) ? $data : [];
    }

    /**
     * Save array data to a JSON file.
     */
    protected function saveToFile(string $path, array $data): void
    {
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, Json::encode($data));
    }

    /**
     * Reload from disk.
     */
    public function refresh(): void
    {
        $this->statesLoaded = false;
        $this->metadataLoaded = false;
        $this->states = [];
        $this->metadata = [];
    }
}
