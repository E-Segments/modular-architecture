<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Discovery;

use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleCollection;
use Esegments\ModularArchitecture\Module\ModuleLoader;
use Illuminate\Support\Facades\Log;

class ModuleDiscovery
{
    protected ?ModuleCollection $discovered = null;

    protected bool $logging = false;

    public function __construct(
        protected readonly PathScanner $scanner,
        protected readonly ModuleStateManager $stateManager,
        protected readonly ?ModuleLoader $loader = null,
    ) {}

    public function enableLogging(bool $enable = true): self
    {
        $this->logging = $enable;

        return $this;
    }

    /**
     * Discover all modules from configured paths.
     */
    public function discover(bool $fresh = false): ModuleCollection
    {
        if ($this->discovered !== null && ! $fresh) {
            return $this->discovered;
        }

        $this->discovered = new ModuleCollection;
        $modulePaths = $this->scanner->scan();

        foreach ($modulePaths as $path) {
            try {
                $module = $this->loadModule($path);
                if ($module) {
                    $this->discovered->put($module->getName(), $module);
                }
            } catch (\Exception $e) {
                $this->logError("Failed to load module at {$path}: ".$e->getMessage());
            }
        }

        $this->log("Discovered {$this->discovered->count()} modules");

        return $this->discovered;
    }

    /**
     * Load a single module from a path.
     */
    protected function loadModule(string $path): ?Module
    {
        try {
            // Use injected loader if available, otherwise fallback to static method
            $module = $this->loader
                ? $this->loader->load($path)
                : Module::fromPath($path);

            // Apply saved state (enabled/disabled)
            $state = $this->stateManager->getState($module->getName());
            if ($state !== null) {
                if ($state) {
                    $module->enable();
                } else {
                    $module->disable();
                }
            }

            // Apply protected status from config
            if ($this->stateManager->isProtected($module->getName())) {
                $module->setProtected(true);
            }

            return $module;
        } catch (\Exception $e) {
            $this->logError("Error loading module from {$path}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Find a specific module by name.
     */
    public function find(string $name): ?Module
    {
        $modules = $this->discover();

        return $modules->findByName($name);
    }

    /**
     * Check if a module exists.
     */
    public function exists(string $name): bool
    {
        return $this->find($name) !== null;
    }

    /**
     * Get all enabled modules.
     */
    public function enabled(): ModuleCollection
    {
        return $this->discover()->enabled();
    }

    /**
     * Get all disabled modules.
     */
    public function disabled(): ModuleCollection
    {
        return $this->discover()->disabled();
    }

    /**
     * Clear discovered modules cache.
     */
    public function clear(): void
    {
        $this->discovered = null;
    }

    /**
     * Refresh discovery (alias for clear + discover).
     */
    public function refresh(): ModuleCollection
    {
        $this->clear();
        $this->stateManager->reload();

        return $this->discover();
    }

    protected function log(string $message): void
    {
        if ($this->logging) {
            Log::debug("[ModularArchitecture] {$message}");
        }
    }

    protected function logError(string $message): void
    {
        if ($this->logging) {
            Log::error("[ModularArchitecture] {$message}");
        }
    }
}
