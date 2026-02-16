<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Blade;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

/**
 * Blade Bridge.
 *
 * Auto-registers Blade views and components from enabled modules.
 *
 * Features:
 * - View namespaces: Module views accessible via module-name::view
 * - Anonymous components: resources/views/components/*.blade.php
 * - Class components: app/View/Components/*.php
 *
 * Example:
 *
 *   @include('products::partials.card')
 *   <x-products::button />
 */
class BladeBridge extends AbstractBridge
{
    /**
     * Discovered view paths.
     *
     * @var array<string, string>
     */
    protected array $viewPaths = [];

    /**
     * Discovered class components.
     *
     * @var array<string, string>
     */
    protected array $classComponents = [];

    public function name(): string
    {
        return 'blade';
    }

    protected function configKey(): string
    {
        return 'blade';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\View\\Compilers\\BladeCompiler';
    }

    protected function componentPath(): string
    {
        return 'resources/views';
    }

    public function registerModule(Module $module): void
    {
        $viewsPath = $module->getPath().'/'.$this->componentPath();
        $componentsPath = $module->getPath().'/app/View/Components';

        $hasViews = is_dir($viewsPath);
        $hasComponents = is_dir($componentsPath);

        if (! $hasViews && ! $hasComponents) {
            return;
        }

        $discovered = [
            'views_path' => $hasViews ? $viewsPath : null,
            'components_path' => $hasComponents ? $componentsPath : null,
            'class_components' => [],
        ];

        $prefix = $this->getViewPrefix($module);

        // Register view namespace
        if ($hasViews) {
            $this->viewPaths[$prefix] = $viewsPath;
        }

        // Discover class-based components
        if ($hasComponents) {
            $components = $this->discoverClassComponents($module, $componentsPath);
            $discovered['class_components'] = $components;

            foreach ($components as $alias => $class) {
                $this->classComponents["{$prefix}::{$alias}"] = $class;
            }
        }

        $this->registered->put($module->getName(), $discovered);

        $this->log("Discovered {$module->getName()} Blade components", [
            'has_views' => $hasViews,
            'class_components' => count($discovered['class_components']),
        ]);
    }

    public function boot(): void
    {
        // Register view namespaces
        foreach ($this->viewPaths as $namespace => $path) {
            view()->addNamespace($namespace, $path);
        }

        // Register anonymous component namespaces
        foreach ($this->viewPaths as $namespace => $path) {
            $componentPath = $path.'/components';
            if (is_dir($componentPath)) {
                Blade::anonymousComponentPath($componentPath, $namespace);
            }
        }

        // Register class-based components
        foreach ($this->classComponents as $alias => $class) {
            if (class_exists($class)) {
                Blade::component($class, $alias);
            }
        }

        $this->log('Registered Blade components', [
            'namespaces' => count($this->viewPaths),
            'class_components' => count($this->classComponents),
        ]);
    }

    /**
     * Discover class-based Blade components.
     *
     * @return array<string, string> alias => class
     */
    protected function discoverClassComponents(Module $module, string $path): array
    {
        $files = $this->app->make(Filesystem::class);
        $components = [];
        $namespace = "Modules\\{$module->getName()}\\View\\Components";

        foreach ($files->allFiles($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($path.'/', '', $file->getPathname());
            $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $fullClass = $namespace.'\\'.$className;

            if (! class_exists($fullClass)) {
                continue;
            }

            // Verify it extends Illuminate\View\Component
            if (! is_subclass_of($fullClass, 'Illuminate\\View\\Component')) {
                continue;
            }

            $alias = $this->generateComponentAlias($className);
            $components[$alias] = $fullClass;
        }

        return $components;
    }

    /**
     * Get the view namespace prefix for a module.
     */
    protected function getViewPrefix(Module $module): string
    {
        return Str::kebab($module->getName());
    }

    /**
     * Generate a component alias from the class name.
     */
    protected function generateComponentAlias(string $className): string
    {
        $parts = explode('\\', $className);

        return collect($parts)
            ->map(fn (string $part) => Str::kebab($part))
            ->implode('.');
    }

    /**
     * Get all registered view paths.
     *
     * @return array<string, string>
     */
    public function getViewPaths(): array
    {
        return $this->viewPaths;
    }

    /**
     * Get all registered class components.
     *
     * @return array<string, string>
     */
    public function getClassComponents(): array
    {
        return $this->classComponents;
    }

    /**
     * Load from cache.
     *
     * @param  array<string, mixed>  $cachedData
     */
    public function loadFromCache(array $cachedData): void
    {
        parent::loadFromCache($cachedData);

        $this->viewPaths = $cachedData['view_paths'] ?? [];
        $this->classComponents = $cachedData['class_components'] ?? [];
    }

    /**
     * Get cache data.
     *
     * @return array<string, mixed>
     */
    public function getCacheData(): array
    {
        return array_merge(parent::getCacheData(), [
            'view_paths' => $this->viewPaths,
            'class_components' => $this->classComponents,
        ]);
    }

    /**
     * Get detailed status.
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        return array_merge(parent::getStatus(), [
            'total_view_namespaces' => count($this->viewPaths),
            'total_class_components' => count($this->classComponents),
        ]);
    }
}
