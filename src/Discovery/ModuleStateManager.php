<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Discovery;

use Illuminate\Filesystem\Filesystem;

class ModuleStateManager
{
    protected array $states = [];

    protected bool $loaded = false;

    public function __construct(
        protected readonly Filesystem $files,
        protected readonly string $stateFile,
        protected readonly array $protectedModules = [],
    ) {}

    /**
     * Load state from file.
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        if ($this->files->exists($this->stateFile)) {
            $content = $this->files->get($this->stateFile);
            $data = json_decode($content, true);

            if (is_array($data)) {
                $this->states = $data;
            }
        }

        $this->loaded = true;
    }

    /**
     * Save state to file.
     */
    public function save(): void
    {
        $this->files->put(
            $this->stateFile,
            json_encode($this->states, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Get module enabled/disabled state.
     */
    public function getState(string $name): ?bool
    {
        $this->load();

        return $this->states[$name] ?? null;
    }

    /**
     * Set module state.
     */
    public function setState(string $name, bool $enabled): void
    {
        $this->load();
        $this->states[$name] = $enabled;
        $this->save();
    }

    /**
     * Enable a module.
     */
    public function enable(string $name): void
    {
        $this->setState($name, true);
    }

    /**
     * Disable a module.
     */
    public function disable(string $name): void
    {
        $this->setState($name, false);
    }

    /**
     * Check if module is protected.
     */
    public function isProtected(string $name): bool
    {
        return in_array($name, $this->protectedModules, true);
    }

    /**
     * Get all protected modules.
     *
     * @return array<string>
     */
    public function getProtectedModules(): array
    {
        return $this->protectedModules;
    }

    /**
     * Remove state for a module (when deleted).
     */
    public function removeState(string $name): void
    {
        $this->load();
        unset($this->states[$name]);
        $this->save();
    }

    /**
     * Get all module states.
     *
     * @return array<string, bool>
     */
    public function getAllStates(): array
    {
        $this->load();

        return $this->states;
    }

    /**
     * Clear all states.
     */
    public function clear(): void
    {
        $this->states = [];
        $this->loaded = false;

        if ($this->files->exists($this->stateFile)) {
            $this->files->delete($this->stateFile);
        }
    }
}
