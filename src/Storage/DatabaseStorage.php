<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Storage;

use Esegments\ModularArchitecture\Contracts\ModuleStorageContract;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseStorage implements ModuleStorageContract
{
    protected ?Connection $connection = null;

    public function __construct(
        protected readonly string $table = 'modules',
        protected readonly ?string $connectionName = null,
    ) {}

    public function getState(string $name): ?bool
    {
        $record = $this->query()
            ->where('name', $name)
            ->first();

        return $record ? (bool) $record->enabled : null;
    }

    public function setState(string $name, bool $enabled): void
    {
        $this->query()->updateOrInsert(
            ['name' => $name],
            [
                'enabled' => $enabled,
                'updated_at' => now(),
            ]
        );
    }

    public function getAllStates(): array
    {
        return $this->query()
            ->pluck('enabled', 'name')
            ->map(fn ($enabled) => (bool) $enabled)
            ->toArray();
    }

    public function removeState(string $name): void
    {
        $this->query()->where('name', $name)->delete();
    }

    public function getMetadata(string $name): ?array
    {
        $record = $this->query()
            ->where('name', $name)
            ->first();

        if (! $record || ! $record->metadata) {
            return null;
        }

        return json_decode($record->metadata, true);
    }

    public function setMetadata(string $name, array $metadata): void
    {
        $existing = $this->getMetadata($name) ?? [];
        $merged = array_merge($existing, $metadata, [
            'updated_at' => now()->toIso8601String(),
        ]);

        $this->query()->updateOrInsert(
            ['name' => $name],
            [
                'metadata' => json_encode($merged),
                'updated_at' => now(),
            ]
        );
    }

    public function getAllMetadata(): array
    {
        return $this->query()
            ->whereNotNull('metadata')
            ->pluck('metadata', 'name')
            ->map(fn ($metadata) => json_decode($metadata, true))
            ->toArray();
    }

    public function removeMetadata(string $name): void
    {
        $this->query()
            ->where('name', $name)
            ->update(['metadata' => null, 'updated_at' => now()]);
    }

    public function isAvailable(): bool
    {
        try {
            return Schema::connection($this->connectionName)->hasTable($this->table);
        } catch (QueryException) {
            return false;
        }
    }

    public function clear(): void
    {
        $this->query()->truncate();
    }

    /**
     * Get the query builder for the modules table.
     */
    protected function query()
    {
        return DB::connection($this->connectionName)->table($this->table);
    }

    /**
     * Create the modules table if it doesn't exist.
     */
    public function createTable(): void
    {
        if ($this->isAvailable()) {
            return;
        }

        Schema::connection($this->connectionName)->create($this->table, function ($table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('enabled')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('enabled');
        });
    }

    /**
     * Get the table name.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get full module record.
     */
    public function getRecord(string $name): ?object
    {
        return $this->query()->where('name', $name)->first();
    }

    /**
     * Get all records.
     */
    public function getAllRecords(): array
    {
        return $this->query()->get()->all();
    }
}
