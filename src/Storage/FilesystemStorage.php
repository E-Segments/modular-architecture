<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Storage;

use Esegments\ModularArchitecture\Contracts\ModuleStorageContract;
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
        if ($this->statesLoaded) {
            return;
        }

        if ($this->files->exists($this->statesFile)) {
            $content = $this->files->get($this->statesFile);
            $data = json_decode($content, true);

            if (is_array($data)) {
                $this->states = $data;
            }
        }

        $this->statesLoaded = true;
    }

    protected function saveStates(): void
    {
        $this->files->ensureDirectoryExists(dirname($this->statesFile));
        $this->files->put(
            $this->statesFile,
            json_encode($this->states, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function loadMetadata(): void
    {
        if ($this->metadataLoaded) {
            return;
        }

        if ($this->files->exists($this->metadataFile)) {
            $content = $this->files->get($this->metadataFile);
            $data = json_decode($content, true);

            if (is_array($data)) {
                $this->metadata = $data;
            }
        }

        $this->metadataLoaded = true;
    }

    protected function saveMetadata(): void
    {
        $this->files->ensureDirectoryExists(dirname($this->metadataFile));
        $this->files->put(
            $this->metadataFile,
            json_encode($this->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
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
