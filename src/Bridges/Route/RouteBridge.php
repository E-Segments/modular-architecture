<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Route;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Route Bridge.
 *
 * Auto-loads route files from enabled modules:
 * - routes/web.php -> Web routes with 'web' middleware
 * - routes/api.php -> API routes with 'api' middleware
 * - routes/api/v{n}.php -> Versioned API routes
 * - routes/console.php -> Console routes
 * - routes/channels.php -> Broadcast channels
 *
 * Features:
 * - Automatic route prefixing and naming
 * - Controller namespace auto-configuration
 * - Versioned API support (v1, v2, etc.)
 * - Domain routing support
 * - Configurable middleware per module
 */
class RouteBridge extends AbstractBridge
{
    /**
     * Discovered route files pending loading.
     *
     * @var array<string, array{web?: string, api?: string, console?: string, channels?: string, api_versions?: array<string, string>}>
     */
    protected array $routeFiles = [];

    /**
     * Module-specific middleware configurations.
     *
     * @var array<string, array{web?: array<string>, api?: array<string>}>
     */
    protected array $moduleMiddleware = [];

    public function name(): string
    {
        return 'route';
    }

    protected function configKey(): string
    {
        return 'routes';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Routing\\Router';
    }

    protected function componentPath(): string
    {
        return 'routes';
    }

    public function registerModule(Module $module): void
    {
        $routesPath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($routesPath)) {
            return;
        }

        $discovered = [];

        // Check for each route file type
        $routeTypes = ['web', 'api', 'console', 'channels'];

        foreach ($routeTypes as $type) {
            $file = $routesPath."/{$type}.php";
            if (file_exists($file)) {
                $discovered[$type] = $file;
            }
        }

        // Discover versioned API routes (routes/api/v1.php, routes/api/v2.php, etc.)
        $apiVersionsPath = $routesPath.'/api';
        if (is_dir($apiVersionsPath)) {
            $discovered['api_versions'] = $this->discoverVersionedApiRoutes($apiVersionsPath);
        }

        // Discover domain-specific routes (routes/domains/*.php)
        $domainsPath = $routesPath.'/domains';
        if (is_dir($domainsPath)) {
            $discovered['domains'] = $this->discoverDomainRoutes($domainsPath);
        }

        if (empty($discovered)) {
            return;
        }

        // Check for module-specific middleware config from module.json extra
        $moduleConfig = $module->getManifestObject()->getExtra('routes', []);
        if (! empty($moduleConfig) && is_array($moduleConfig)) {
            $this->moduleMiddleware[$module->getName()] = $moduleConfig;
        }

        $this->registered->put($module->getName(), [
            'path' => $routesPath,
            'files' => $discovered,
            'config' => $moduleConfig,
        ]);

        $this->routeFiles[$module->getName()] = $discovered;

        $this->log("Discovered {$module->getName()} routes", [
            'files' => array_keys($discovered),
            'api_versions' => count($discovered['api_versions'] ?? []),
        ]);
    }

    public function boot(): void
    {
        foreach ($this->routeFiles as $moduleName => $files) {
            $this->loadModuleRoutes($moduleName, $files);
        }
    }

    /**
     * Load routes for a module.
     *
     * @param  array{web?: string, api?: string, console?: string, channels?: string, api_versions?: array<string, string>, domains?: array<string, string>}  $files
     */
    protected function loadModuleRoutes(string $moduleName, array $files): void
    {
        $prefix = $this->getRoutePrefix($moduleName);
        $namespace = $this->getControllerNamespace($moduleName);

        // Load web routes
        if (isset($files['web'])) {
            $this->loadWebRoutes($moduleName, $files['web'], $prefix, $namespace);
        }

        // Load API routes
        if (isset($files['api'])) {
            $this->loadApiRoutes($moduleName, $files['api'], $prefix, $namespace);
        }

        // Load versioned API routes
        if (isset($files['api_versions'])) {
            foreach ($files['api_versions'] as $version => $file) {
                $this->loadVersionedApiRoutes($moduleName, $version, $file, $prefix, $namespace);
            }
        }

        // Load domain routes
        if (isset($files['domains'])) {
            foreach ($files['domains'] as $domain => $file) {
                $this->loadDomainRoutes($moduleName, $domain, $file, $namespace);
            }
        }

        // Load console routes
        if (isset($files['console'])) {
            $this->loadConsoleRoutes($files['console']);
        }

        // Load broadcast channels
        if (isset($files['channels'])) {
            $this->loadChannelRoutes($files['channels']);
        }
    }

    /**
     * Load web routes.
     */
    protected function loadWebRoutes(string $moduleName, string $file, string $prefix, string $namespace): void
    {
        $usePrefix = (bool) config('modular.bridges.routes.prefix_web', true);
        $middlewares = config('modular.bridges.routes.web_middleware', ['web']);

        Route::middleware($middlewares)
            ->namespace($namespace)
            ->name(Str::lower($moduleName).'.')
            ->when($usePrefix, fn ($route) => $route->prefix($prefix))
            ->group($file);
    }

    /**
     * Load API routes.
     */
    protected function loadApiRoutes(string $moduleName, string $file, string $prefix, string $namespace): void
    {
        $usePrefix = (bool) config('modular.bridges.routes.prefix_api', true);
        $middlewares = config('modular.bridges.routes.api_middleware', ['api']);
        $apiPrefix = config('modular.bridges.routes.api_prefix', 'api');

        $fullPrefix = $usePrefix ? "{$apiPrefix}/{$prefix}" : $apiPrefix;

        Route::middleware($middlewares)
            ->namespace($namespace)
            ->prefix($fullPrefix)
            ->name(Str::lower($moduleName).'.api.')
            ->group($file);
    }

    /**
     * Load console routes.
     */
    protected function loadConsoleRoutes(string $file): void
    {
        require $file;
    }

    /**
     * Load broadcast channel routes.
     */
    protected function loadChannelRoutes(string $file): void
    {
        require $file;
    }

    /**
     * Get the URL prefix for a module.
     */
    protected function getRoutePrefix(string $moduleName): string
    {
        return Str::kebab($moduleName);
    }

    /**
     * Get the controller namespace for a module.
     */
    protected function getControllerNamespace(string $moduleName): string
    {
        return "Modules\\{$moduleName}\\Http\\Controllers";
    }

    /**
     * Load versioned API routes.
     */
    protected function loadVersionedApiRoutes(
        string $moduleName,
        string $version,
        string $file,
        string $prefix,
        string $namespace
    ): void {
        $usePrefix = (bool) config('modular.bridges.routes.prefix_api', true);
        $middlewares = $this->getModuleMiddleware($moduleName, 'api');
        $apiPrefix = config('modular.bridges.routes.api_prefix', 'api');

        $fullPrefix = $usePrefix
            ? "{$apiPrefix}/{$version}/{$prefix}"
            : "{$apiPrefix}/{$version}";

        Route::middleware($middlewares)
            ->namespace($namespace)
            ->prefix($fullPrefix)
            ->name(Str::lower($moduleName).".api.{$version}.")
            ->group($file);
    }

    /**
     * Load domain-specific routes.
     */
    protected function loadDomainRoutes(
        string $moduleName,
        string $domain,
        string $file,
        string $namespace
    ): void {
        $middlewares = $this->getModuleMiddleware($moduleName, 'web');

        Route::middleware($middlewares)
            ->namespace($namespace)
            ->domain($domain)
            ->name(Str::lower($moduleName).'.domain.')
            ->group($file);
    }

    /**
     * Discover versioned API route files.
     *
     * @return array<string, string> version => file path
     */
    protected function discoverVersionedApiRoutes(string $apiPath): array
    {
        $versions = [];

        foreach (glob($apiPath.'/v*.php') as $file) {
            // Extract version from filename (v1.php -> v1)
            $version = pathinfo($file, PATHINFO_FILENAME);
            $versions[$version] = $file;
        }

        // Sort by version number
        uksort($versions, function ($a, $b) {
            return version_compare(
                str_replace('v', '', $a),
                str_replace('v', '', $b)
            );
        });

        return $versions;
    }

    /**
     * Discover domain-specific route files.
     *
     * @return array<string, string> domain => file path
     */
    protected function discoverDomainRoutes(string $domainsPath): array
    {
        $domains = [];

        foreach (glob($domainsPath.'/*.php') as $file) {
            // Filename is the domain (api.example.com.php -> api.example.com)
            $domain = pathinfo($file, PATHINFO_FILENAME);
            $domains[$domain] = $file;
        }

        return $domains;
    }

    /**
     * Get middleware for a module and route type.
     *
     * @return array<string>
     */
    protected function getModuleMiddleware(string $moduleName, string $type): array
    {
        // Check module-specific middleware first
        if (isset($this->moduleMiddleware[$moduleName][$type.'_middleware'])) {
            return $this->moduleMiddleware[$moduleName][$type.'_middleware'];
        }

        // Fall back to global config
        return config("modular.bridges.routes.{$type}_middleware", [$type]);
    }

    /**
     * Get all discovered route files.
     *
     * @return array<string, array{web?: string, api?: string, console?: string, channels?: string, api_versions?: array<string, string>, domains?: array<string, string>}>
     */
    public function getRouteFiles(): array
    {
        return $this->routeFiles;
    }

    /**
     * Get discovered API versions for a module.
     *
     * @return array<string, string>
     */
    public function getApiVersions(string $moduleName): array
    {
        return $this->routeFiles[$moduleName]['api_versions'] ?? [];
    }

    /**
     * Load from cache.
     *
     * @param  array<string, mixed>  $cachedData
     */
    public function loadFromCache(array $cachedData): void
    {
        parent::loadFromCache($cachedData);

        $this->routeFiles = $cachedData['route_files'] ?? [];
        $this->moduleMiddleware = $cachedData['module_middleware'] ?? [];
    }

    /**
     * Get cache data.
     *
     * @return array<string, mixed>
     */
    public function getCacheData(): array
    {
        return array_merge(parent::getCacheData(), [
            'route_files' => $this->routeFiles,
            'module_middleware' => $this->moduleMiddleware,
        ]);
    }

    /**
     * Get detailed status.
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        $totalVersions = 0;
        $totalDomains = 0;

        foreach ($this->routeFiles as $files) {
            $totalVersions += count($files['api_versions'] ?? []);
            $totalDomains += count($files['domains'] ?? []);
        }

        return array_merge(parent::getStatus(), [
            'total_api_versions' => $totalVersions,
            'total_domain_routes' => $totalDomains,
        ]);
    }
}
