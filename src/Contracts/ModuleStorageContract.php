<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Contracts;

interface ModuleStorageContract
{
    /**
     * Get module state (enabled/disabled).
     */
    public function getState(string $name): ?bool;

    /**
     * Set module state.
     */
    public function setState(string $name, bool $enabled): void;

    /**
     * Get all module states.
     *
     * @return array<string, bool>
     */
    public function getAllStates(): array;

    /**
     * Remove state for a module.
     */
    public function removeState(string $name): void;

    /**
     * Get module metadata.
     *
     * @return array<string, mixed>|null
     */
    public function getMetadata(string $name): ?array;

    /**
     * Set module metadata.
     *
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(string $name, array $metadata): void;

    /**
     * Get all module metadata.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllMetadata(): array;

    /**
     * Remove metadata for a module.
     */
    public function removeMetadata(string $name): void;

    /**
     * Check if storage is available.
     */
    public function isAvailable(): bool;

    /**
     * Clear all stored data.
     */
    public function clear(): void;
}
