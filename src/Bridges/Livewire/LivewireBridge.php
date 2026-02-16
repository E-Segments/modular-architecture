<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Livewire;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Livewire Bridge.
 *
 * Auto-discovers and registers Livewire components from enabled modules.
 * Components are registered with a module prefix for namespacing.
 *
 * Example:
 *   Modules/Products/app/Livewire/ProductTable.php
 *   Registered as: <livewire:products::product-table />
 */
class LivewireBridge extends AbstractBridge
{
    /**
     * Discovered components pending registration.
     *
     * @var array<string, string>
     */
    protected array $components = [];

    public function name(): string
    {
        return 'livewire';
    }

    protected function configKey(): string
    {
        return 'livewire';
    }

    protected function requiredClass(): string
    {
        return 'Livewire\\Livewire';
    }

    protected function componentPath(): string
    {
        return 'app/Livewire';
    }

    public function registerModule(Module $module): void
    {
        $livewirePath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($livewirePath)) {
            return;
        }

        $discovered = $this->discoverComponents($module, $livewirePath);

        if (empty($discovered)) {
            return;
        }

        $this->registered->put($module->getName(), [
            'path' => $livewirePath,
            'components' => $discovered,
        ]);

        $prefix = $this->getComponentPrefix($module);

        foreach ($discovered as $alias => $class) {
            $componentName = "{$prefix}::{$alias}";
            $this->components[$componentName] = $class;
        }

        $this->log("Discovered {$module->getName()} Livewire components", [
            'count' => count($discovered),
            'prefix' => $prefix,
        ]);
    }

    public function boot(): void
    {
        if (empty($this->components)) {
            return;
        }

        $livewire = $this->app->make('livewire');

        foreach ($this->components as $name => $class) {
            if (class_exists($class)) {
                $livewire->component($name, $class);
            }
        }

        $this->log('Registered Livewire components', [
            'count' => count($this->components),
        ]);
    }

    /**
     * Discover Livewire components in a directory.
     *
     * @return array<string, string> Map of alias => class
     */
    protected function discoverComponents(Module $module, string $path): array
    {
        $files = $this->app->make(Filesystem::class);
        $components = [];
        $namespace = $this->getComponentNamespace($module);

        foreach ($files->allFiles($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Build the class name from the file path
            $relativePath = str_replace($path.'/', '', $file->getPathname());
            $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $fullClass = $namespace.'\\'.$className;

            if (! class_exists($fullClass)) {
                continue;
            }

            // Verify it extends Livewire Component
            if (! is_subclass_of($fullClass, 'Livewire\\Component')) {
                continue;
            }

            // Generate the component alias
            $alias = $this->generateAlias($className);
            $components[$alias] = $fullClass;
        }

        return $components;
    }

    /**
     * Get the namespace for Livewire components in a module.
     */
    protected function getComponentNamespace(Module $module): string
    {
        return "Modules\\{$module->getName()}\\Livewire";
    }

    /**
     * Get the component prefix for a module.
     */
    protected function getComponentPrefix(Module $module): string
    {
        return Str::kebab($module->getName());
    }

    /**
     * Generate a component alias from the class name.
     *
     * ProductTable -> product-table
     * Admin/UserList -> admin.user-list
     */
    protected function generateAlias(string $className): string
    {
        $parts = explode('\\', $className);

        $aliasedParts = array_map(
            fn (string $part) => Str::kebab($part),
            $parts
        );

        return implode('.', $aliasedParts);
    }

    /**
     * Get all registered components.
     *
     * @return array<string, string>
     */
    public function getComponents(): array
    {
        return $this->components;
    }
}
