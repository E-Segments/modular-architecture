<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Contracts;

use Esegments\ModularArchitecture\Module\Module;

/**
 * Contract for framework bridges.
 *
 * Bridges auto-discover and register framework-specific components
 * (Livewire, Filament, Routes, Views, etc.) from enabled modules.
 */
interface BridgeContract
{
    /**
     * Get the bridge name.
     */
    public function name(): string;

    /**
     * Check if this bridge is available (framework is installed).
     */
    public function isAvailable(): bool;

    /**
     * Check if this bridge is enabled via config.
     */
    public function isEnabled(): bool;

    /**
     * Register components from a single module.
     */
    public function registerModule(Module $module): void;

    /**
     * Boot the bridge after all modules are registered.
     */
    public function boot(): void;
}
