<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Filament;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Filesystem\Filesystem;

/**
 * Filament Bridge.
 *
 * Auto-discovers and registers Filament resources, pages, widgets,
 * relation managers, and clusters from enabled modules.
 *
 * Supports Filament 3, 4, and 5 structures.
 *
 * For panel registration, use the discoverModules() method in your
 * PanelProvider:
 *
 * ```php
 * public function panel(Panel $panel): Panel
 * {
 *     return $panel
 *         ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
 *         // Add module resources via bridge
 *         ->resources(app(FilamentBridge::class)->getResources())
 *         ->pages(app(FilamentBridge::class)->getPages())
 *         ->widgets(app(FilamentBridge::class)->getWidgets());
 * }
 * ```
 */
class FilamentBridge extends AbstractBridge
{
    /**
     * Discovered Filament components pending registration.
     *
     * @var array{resources: array<string>, pages: array<string>, widgets: array<string>, relation_managers: array<string>, clusters: array<string>}
     */
    protected array $discovered = [
        'resources' => [],
        'pages' => [],
        'widgets' => [],
        'relation_managers' => [],
        'clusters' => [],
    ];

    public function name(): string
    {
        return 'filament';
    }

    protected function configKey(): string
    {
        return 'filament';
    }

    protected function requiredClass(): string
    {
        return 'Filament\\Panel';
    }

    protected function componentPath(): string
    {
        return 'app/Filament';
    }

    public function registerModule(Module $module): void
    {
        $filamentPath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($filamentPath)) {
            return;
        }

        $moduleDiscovered = [
            'resources' => $this->discoverResources($module, $filamentPath),
            'pages' => $this->discoverPages($module, $filamentPath),
            'widgets' => $this->discoverWidgets($module, $filamentPath),
            'relation_managers' => $this->discoverRelationManagers($module, $filamentPath),
            'clusters' => $this->discoverClusters($module, $filamentPath),
        ];

        $totalCount = count($moduleDiscovered['resources'])
            + count($moduleDiscovered['pages'])
            + count($moduleDiscovered['widgets'])
            + count($moduleDiscovered['relation_managers'])
            + count($moduleDiscovered['clusters']);

        if ($totalCount === 0) {
            return;
        }

        $this->registered->put($module->getName(), $moduleDiscovered);

        // Merge into global discovered arrays
        foreach (['resources', 'pages', 'widgets', 'relation_managers', 'clusters'] as $type) {
            $this->discovered[$type] = array_merge(
                $this->discovered[$type],
                $moduleDiscovered[$type]
            );
        }

        $this->log("Discovered {$module->getName()} Filament components", [
            'resources' => count($moduleDiscovered['resources']),
            'pages' => count($moduleDiscovered['pages']),
            'widgets' => count($moduleDiscovered['widgets']),
            'relation_managers' => count($moduleDiscovered['relation_managers']),
            'clusters' => count($moduleDiscovered['clusters']),
        ]);
    }

    public function boot(): void
    {
        // Filament components are typically registered via the Panel configuration
        // or auto-discovered. This bridge collects them for manual registration
        // or for the panel provider to access.

        // For auto-registration, bind to Filament's discovery if available
        if ($this->app->bound('filament')) {
            $this->registerWithFilament();
        }
    }

    /**
     * Register components with Filament panels.
     */
    protected function registerWithFilament(): void
    {
        // Get the default panel
        $filament = $this->app->make('filament');

        // Register resources, pages, and widgets
        // Note: This depends on how Filament's API works in the installed version
        // Most projects configure this in the PanelProvider instead

        $this->log('Filament components available for registration', [
            'resources' => count($this->discovered['resources']),
            'pages' => count($this->discovered['pages']),
            'widgets' => count($this->discovered['widgets']),
        ]);
    }

    /**
     * Discover Filament resources.
     *
     * @return array<string>
     */
    protected function discoverResources(Module $module, string $basePath): array
    {
        $resourcesPath = $basePath.'/Resources';

        if (! is_dir($resourcesPath)) {
            return [];
        }

        return $this->discoverClassesInDirectory(
            $module,
            $resourcesPath,
            'Filament\\Resources',
            'Filament\\Resources\\Resource'
        );
    }

    /**
     * Discover Filament pages.
     *
     * @return array<string>
     */
    protected function discoverPages(Module $module, string $basePath): array
    {
        $pagesPath = $basePath.'/Pages';

        if (! is_dir($pagesPath)) {
            return [];
        }

        return $this->discoverClassesInDirectory(
            $module,
            $pagesPath,
            'Filament\\Pages',
            'Filament\\Pages\\Page'
        );
    }

    /**
     * Discover Filament widgets.
     *
     * @return array<string>
     */
    protected function discoverWidgets(Module $module, string $basePath): array
    {
        $widgetsPath = $basePath.'/Widgets';

        if (! is_dir($widgetsPath)) {
            return [];
        }

        return $this->discoverClassesInDirectory(
            $module,
            $widgetsPath,
            'Filament\\Widgets',
            'Filament\\Widgets\\Widget'
        );
    }

    /**
     * Discover classes in a directory that extend a base class.
     *
     * @return array<string>
     */
    protected function discoverClassesInDirectory(
        Module $module,
        string $path,
        string $namespaceSegment,
        string $baseClass
    ): array {
        $files = $this->app->make(Filesystem::class);
        $classes = [];
        $namespace = "Modules\\{$module->getName()}\\{$namespaceSegment}";

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

            if (! is_subclass_of($fullClass, $baseClass)) {
                continue;
            }

            $classes[] = $fullClass;
        }

        return $classes;
    }

    /**
     * Get all discovered resources.
     *
     * @return array<string>
     */
    public function getResources(): array
    {
        return $this->discovered['resources'];
    }

    /**
     * Get all discovered pages.
     *
     * @return array<string>
     */
    public function getPages(): array
    {
        return $this->discovered['pages'];
    }

    /**
     * Get all discovered widgets.
     *
     * @return array<string>
     */
    public function getWidgets(): array
    {
        return $this->discovered['widgets'];
    }

    /**
     * Discover Filament relation managers.
     *
     * @return array<string>
     */
    protected function discoverRelationManagers(Module $module, string $basePath): array
    {
        // Relation managers are typically inside Resources/*/RelationManagers
        $resourcesPath = $basePath.'/Resources';

        if (! is_dir($resourcesPath)) {
            return [];
        }

        $files = $this->app->make(Filesystem::class);
        $relationManagers = [];
        $namespace = "Modules\\{$module->getName()}\\Filament\\Resources";

        foreach ($files->directories($resourcesPath) as $resourceDir) {
            $rmPath = $resourceDir.'/RelationManagers';

            if (! is_dir($rmPath)) {
                continue;
            }

            $resourceName = basename($resourceDir);

            foreach ($files->files($rmPath) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $className = $file->getBasename('.php');
                $fullClass = "{$namespace}\\{$resourceName}\\RelationManagers\\{$className}";

                if (class_exists($fullClass) && is_subclass_of($fullClass, 'Filament\\Resources\\RelationManagers\\RelationManager')) {
                    $relationManagers[] = $fullClass;
                }
            }
        }

        return $relationManagers;
    }

    /**
     * Discover Filament clusters.
     *
     * @return array<string>
     */
    protected function discoverClusters(Module $module, string $basePath): array
    {
        $clustersPath = $basePath.'/Clusters';

        if (! is_dir($clustersPath)) {
            return [];
        }

        return $this->discoverClassesInDirectory(
            $module,
            $clustersPath,
            'Filament\\Clusters',
            'Filament\\Clusters\\Cluster'
        );
    }

    /**
     * Get all discovered relation managers.
     *
     * @return array<string>
     */
    public function getRelationManagers(): array
    {
        return $this->discovered['relation_managers'];
    }

    /**
     * Get all discovered clusters.
     *
     * @return array<string>
     */
    public function getClusters(): array
    {
        return $this->discovered['clusters'];
    }

    /**
     * Get all discovered components as a summary.
     *
     * @return array{resources: int, pages: int, widgets: int, relation_managers: int, clusters: int}
     */
    public function getSummary(): array
    {
        return [
            'resources' => count($this->discovered['resources']),
            'pages' => count($this->discovered['pages']),
            'widgets' => count($this->discovered['widgets']),
            'relation_managers' => count($this->discovered['relation_managers']),
            'clusters' => count($this->discovered['clusters']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromCache(array $cachedData): void
    {
        parent::loadFromCache($cachedData);

        $this->discovered = $cachedData['discovered'] ?? [
            'resources' => [],
            'pages' => [],
            'widgets' => [],
            'relation_managers' => [],
            'clusters' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheData(): array
    {
        return array_merge(parent::getCacheData(), [
            'discovered' => $this->discovered,
        ]);
    }
}
