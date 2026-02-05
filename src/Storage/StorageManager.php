<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Storage;

use Esegments\ModularArchitecture\Contracts\ModuleStorageContract;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

class StorageManager
{
    protected ?ModuleStorageContract $driver = null;

    /** @var array<string, callable> Custom driver creators */
    protected array $customCreators = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected readonly Filesystem $files,
        protected readonly array $config,
    ) {}

    /**
     * Register a custom driver creator.
     *
     * @param  callable(array<string, mixed>): ModuleStorageContract  $callback
     */
    public function extend(string $driver, callable $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Get the storage driver.
     */
    public function driver(?string $name = null): ModuleStorageContract
    {
        $name = $name ?? $this->getDefaultDriver();

        if ($this->driver !== null) {
            return $this->driver;
        }

        $this->driver = $this->resolve($name);

        return $this->driver;
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config['driver'] ?? 'file';
    }

    /**
     * Resolve the given driver.
     */
    protected function resolve(string $name): ModuleStorageContract
    {
        // Check for custom driver first
        if (isset($this->customCreators[$name])) {
            return call_user_func($this->customCreators[$name], $this->config);
        }

        return match ($name) {
            'file', 'filesystem' => $this->createFilesystemDriver(),
            'database', 'db' => $this->createDatabaseDriver(),
            default => throw new InvalidArgumentException("Driver [{$name}] is not supported."),
        };
    }

    /**
     * Create a filesystem driver instance.
     */
    protected function createFilesystemDriver(): FilesystemStorage
    {
        $basePath = $this->config['path'] ?? storage_path('modular');

        return new FilesystemStorage(
            files: $this->files,
            statesFile: $this->config['states_file'] ?? "{$basePath}/modules_statuses.json",
            metadataFile: $this->config['metadata_file'] ?? "{$basePath}/modules_metadata.json",
        );
    }

    /**
     * Create a database driver instance.
     */
    protected function createDatabaseDriver(): DatabaseStorage
    {
        $storage = new DatabaseStorage(
            table: $this->config['table'] ?? 'modules',
            connectionName: $this->config['connection'] ?? null,
        );

        // Auto-create table if configured
        if ($this->config['auto_create_table'] ?? false) {
            $storage->createTable();
        }

        return $storage;
    }

    /**
     * Check if the current driver is available.
     */
    public function isAvailable(): bool
    {
        return $this->driver()->isAvailable();
    }

    /**
     * Migrate data from one driver to another.
     */
    public function migrate(string $from, string $to): array
    {
        $sourceDriver = $this->resolve($from);
        $targetDriver = $this->resolve($to);

        if (! $sourceDriver->isAvailable()) {
            throw new InvalidArgumentException("Source driver [{$from}] is not available.");
        }

        if (! $targetDriver->isAvailable()) {
            throw new InvalidArgumentException("Target driver [{$to}] is not available.");
        }

        $migrated = [
            'states' => 0,
            'metadata' => 0,
        ];

        // Migrate states
        foreach ($sourceDriver->getAllStates() as $name => $enabled) {
            $targetDriver->setState($name, $enabled);
            $migrated['states']++;
        }

        // Migrate metadata
        foreach ($sourceDriver->getAllMetadata() as $name => $metadata) {
            $targetDriver->setMetadata($name, $metadata);
            $migrated['metadata']++;
        }

        return $migrated;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  array<mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->$method(...$parameters);
    }
}
