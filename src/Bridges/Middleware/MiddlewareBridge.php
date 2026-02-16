<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Middleware;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Middleware Bridge.
 *
 * Auto-discovers and registers HTTP middleware from enabled modules.
 *
 * Discovery path: app/Http/Middleware/*.php
 *
 * Middleware is registered with an alias based on module and class name:
 * - products.check-stock -> Modules\Products\Http\Middleware\CheckStock
 *
 * Middleware can define a static $alias property to customize the alias.
 */
class MiddlewareBridge extends AbstractBridge
{
    /**
     * Discovered middleware.
     *
     * @var array<string, string> Alias => Class
     */
    protected array $middleware = [];

    /**
     * Global middleware to register.
     *
     * @var array<string>
     */
    protected array $globalMiddleware = [];

    /**
     * Middleware groups to extend.
     *
     * @var array<string, array<string>>
     */
    protected array $middlewareGroups = [];

    public function name(): string
    {
        return 'middleware';
    }

    protected function configKey(): string
    {
        return 'middleware';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Http\\Middleware\\HandleCors';
    }

    protected function componentPath(): string
    {
        return 'app/Http/Middleware';
    }

    public function registerModule(Module $module): void
    {
        $middlewarePath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($middlewarePath)) {
            return;
        }

        $discovered = $this->discoverMiddleware($module, $middlewarePath);

        if (empty($discovered)) {
            return;
        }

        $this->registered->put($module->getName(), [
            'path' => $middlewarePath,
            'middleware' => $discovered,
        ]);

        $this->middleware = array_merge($this->middleware, $discovered);

        $this->log("Discovered {$module->getName()} middleware", [
            'count' => count($discovered),
        ]);
    }

    public function boot(): void
    {
        if (empty($this->middleware)) {
            return;
        }

        $router = $this->app->make(Router::class);

        // Register aliased middleware
        foreach ($this->middleware as $alias => $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $router->aliasMiddleware($alias, $middlewareClass);
            }
        }

        // Register global middleware
        if (! empty($this->globalMiddleware)) {
            $kernel = $this->app->make(Kernel::class);
            foreach ($this->globalMiddleware as $middleware) {
                $kernel->pushMiddleware($middleware);
            }
        }

        // Extend middleware groups
        foreach ($this->middlewareGroups as $group => $middlewareList) {
            foreach ($middlewareList as $middleware) {
                $router->pushMiddlewareToGroup($group, $middleware);
            }
        }

        $this->log('Registered middleware', [
            'aliased' => count($this->middleware),
            'global' => count($this->globalMiddleware),
            'groups' => count($this->middlewareGroups),
        ]);
    }

    /**
     * Discover middleware classes.
     *
     * @return array<string, string> Alias => Class
     */
    protected function discoverMiddleware(Module $module, string $middlewarePath): array
    {
        $files = $this->app->make(Filesystem::class);
        $middleware = [];
        $namespace = "Modules\\{$module->getName()}\\Http\\Middleware";
        $prefix = Str::kebab($module->getName());

        foreach ($files->allFiles($middlewarePath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($middlewarePath.'/', '', $file->getPathname());
            $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $middlewareClass = $namespace.'\\'.$className;

            if (! class_exists($middlewareClass)) {
                continue;
            }

            // Get alias from static property or generate from class name
            $alias = $this->resolveAlias($middlewareClass, $prefix, $className);
            $middleware[$alias] = $middlewareClass;

            // Check for global or group registration
            $this->checkMiddlewareType($middlewareClass);
        }

        return $middleware;
    }

    /**
     * Resolve the middleware alias.
     */
    protected function resolveAlias(string $middlewareClass, string $prefix, string $className): string
    {
        // Check for static $alias property
        try {
            $reflection = new ReflectionClass($middlewareClass);
            if ($reflection->hasProperty('alias')) {
                $property = $reflection->getProperty('alias');
                if ($property->isStatic() && $property->isPublic()) {
                    return $property->getValue();
                }
            }
        } catch (\ReflectionException $e) {
            // Ignore reflection errors
        }

        // Generate alias: module.middleware-name
        $middlewareName = Str::kebab(class_basename($className));

        return "{$prefix}.{$middlewareName}";
    }

    /**
     * Check if middleware should be registered globally or in a group.
     */
    protected function checkMiddlewareType(string $middlewareClass): void
    {
        try {
            $reflection = new ReflectionClass($middlewareClass);

            // Check for global registration
            if ($reflection->hasProperty('global')) {
                $property = $reflection->getProperty('global');
                if ($property->isStatic() && $property->isPublic() && $property->getValue()) {
                    $this->globalMiddleware[] = $middlewareClass;
                }
            }

            // Check for group registration
            if ($reflection->hasProperty('groups')) {
                $property = $reflection->getProperty('groups');
                if ($property->isStatic() && $property->isPublic()) {
                    $groups = $property->getValue();
                    foreach ((array) $groups as $group) {
                        if (! isset($this->middlewareGroups[$group])) {
                            $this->middlewareGroups[$group] = [];
                        }
                        $this->middlewareGroups[$group][] = $middlewareClass;
                    }
                }
            }
        } catch (\ReflectionException $e) {
            // Ignore reflection errors
        }
    }

    /**
     * Get all discovered middleware.
     *
     * @return array<string, string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get global middleware.
     *
     * @return array<string>
     */
    public function getGlobalMiddleware(): array
    {
        return $this->globalMiddleware;
    }

    /**
     * Get middleware groups.
     *
     * @return array<string, array<string>>
     */
    public function getMiddlewareGroups(): array
    {
        return $this->middlewareGroups;
    }
}
