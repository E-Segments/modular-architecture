<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Service;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Service Bridge.
 *
 * Auto-discovers and binds service contracts to their implementations.
 *
 * Discovery paths:
 * - app/Contracts/*.php -> Interface definitions
 * - app/Services/*.php -> Service implementations
 *
 * Binding conventions:
 * 1. ProductServiceContract -> ProductService
 * 2. ProductRepositoryContract -> ProductRepository
 * 3. Interface with @see annotation pointing to implementation
 *
 * Services can be bound as:
 * - Singletons (default for *Repository, *Manager)
 * - Regular bindings (default for *Service, *Handler)
 *
 * Example:
 * ```php
 * // app/Contracts/ProductServiceContract.php
 * interface ProductServiceContract {
 *     public function findById(int $id): ?Product;
 * }
 *
 * // app/Services/ProductService.php
 * class ProductService implements ProductServiceContract {
 *     public function findById(int $id): ?Product { ... }
 * }
 *
 * // Auto-bound: ProductServiceContract -> ProductService
 * ```
 */
class ServiceBridge extends AbstractBridge
{
    /**
     * Discovered bindings.
     *
     * @var array<string, array{implementation: string, singleton: bool}>
     */
    protected array $bindings = [];

    /**
     * Patterns that should be bound as singletons.
     *
     * @var array<string>
     */
    protected array $singletonPatterns = [
        'Repository',
        'Manager',
        'Factory',
        'Registry',
        'Cache',
        'Client',
    ];

    public function name(): string
    {
        return 'service';
    }

    protected function configKey(): string
    {
        return 'services';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Container\\Container';
    }

    protected function componentPath(): string
    {
        return 'app/Contracts';
    }

    public function registerModule(Module $module): void
    {
        $contractsPath = $module->getPath().'/'.$this->componentPath();
        $servicesPath = $module->getPath().'/app/Services';

        if (! is_dir($contractsPath)) {
            return;
        }

        $discovered = $this->discoverBindings($module, $contractsPath, $servicesPath);

        if (empty($discovered)) {
            return;
        }

        $this->registered->put($module->getName(), [
            'contracts_path' => $contractsPath,
            'services_path' => $servicesPath,
            'bindings' => $discovered,
        ]);

        $this->bindings = array_merge($this->bindings, $discovered);

        $this->log("Discovered {$module->getName()} service bindings", [
            'count' => count($discovered),
        ]);
    }

    public function boot(): void
    {
        if (empty($this->bindings)) {
            return;
        }

        foreach ($this->bindings as $contract => $binding) {
            if (! interface_exists($contract) || ! class_exists($binding['implementation'])) {
                continue;
            }

            if ($binding['singleton']) {
                $this->app->singleton($contract, $binding['implementation']);
            } else {
                $this->app->bind($contract, $binding['implementation']);
            }
        }

        $this->log('Registered service bindings', [
            'count' => count($this->bindings),
        ]);
    }

    /**
     * Discover contract-to-implementation bindings.
     *
     * @return array<string, array{implementation: string, singleton: bool}>
     */
    protected function discoverBindings(Module $module, string $contractsPath, string $servicesPath): array
    {
        $files = $this->app->make(Filesystem::class);
        $bindings = [];
        $contractNamespace = "Modules\\{$module->getName()}\\Contracts";
        $serviceNamespace = "Modules\\{$module->getName()}\\Services";

        foreach ($files->allFiles($contractsPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($contractsPath.'/', '', $file->getPathname());
            $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $contractClass = $contractNamespace.'\\'.$className;

            if (! interface_exists($contractClass)) {
                continue;
            }

            // Try to find the implementation
            $implementation = $this->resolveImplementation(
                $contractClass,
                $className,
                $serviceNamespace,
                $servicesPath
            );

            if ($implementation && class_exists($implementation)) {
                // Verify implementation implements the contract
                if (! is_subclass_of($implementation, $contractClass)) {
                    continue;
                }

                $bindings[$contractClass] = [
                    'implementation' => $implementation,
                    'singleton' => $this->shouldBeSingleton($className, $implementation),
                ];
            }
        }

        return $bindings;
    }

    /**
     * Resolve the implementation class for a contract.
     */
    protected function resolveImplementation(
        string $contractClass,
        string $contractName,
        string $serviceNamespace,
        string $servicesPath
    ): ?string {
        // Strategy 1: Check @see annotation in contract docblock
        $implementation = $this->resolveFromDocblock($contractClass);
        if ($implementation) {
            return $implementation;
        }

        // Strategy 2: Remove "Contract" or "Interface" suffix
        $baseName = $this->removeContractSuffix($contractName);

        // Try: ProductServiceContract -> ProductService
        $serviceClass = $serviceNamespace.'\\'.$baseName;
        if (class_exists($serviceClass)) {
            return $serviceClass;
        }

        // Strategy 3: Check for implementation in same directory structure
        // Contracts/Orders/OrderServiceContract -> Services/Orders/OrderService
        $parts = explode('\\', $baseName);
        if (count($parts) > 1) {
            $nestedPath = implode('\\', $parts);
            $serviceClass = $serviceNamespace.'\\'.$nestedPath;
            if (class_exists($serviceClass)) {
                return $serviceClass;
            }
        }

        // Strategy 4: Look in Repositories namespace for *RepositoryContract
        if (Str::endsWith($contractName, 'RepositoryContract') || Str::endsWith($contractName, 'RepositoryInterface')) {
            $repoNamespace = 'Modules\\'.Str::before($serviceNamespace, '\\Services').'\\Repositories';
            $repoClass = str_replace('\\Services\\', '\\Repositories\\', $serviceClass);
            if (class_exists($repoClass)) {
                return $repoClass;
            }
        }

        return null;
    }

    /**
     * Try to resolve implementation from contract's @see docblock.
     */
    protected function resolveFromDocblock(string $contractClass): ?string
    {
        try {
            $reflection = new ReflectionClass($contractClass);
            $docComment = $reflection->getDocComment();

            if ($docComment === false) {
                return null;
            }

            // Look for @see ClassName
            if (preg_match('/@see\s+([a-zA-Z0-9_\\\\]+)/', $docComment, $matches)) {
                $seeClass = $matches[1];

                // If it's a fully qualified class name
                if (class_exists($seeClass)) {
                    return $seeClass;
                }

                // Try relative to contract namespace
                $contractNamespace = $reflection->getNamespaceName();
                $fullClass = $contractNamespace.'\\'.$seeClass;
                if (class_exists($fullClass)) {
                    return $fullClass;
                }

                // Try in Services namespace
                $servicesNamespace = str_replace('\\Contracts', '\\Services', $contractNamespace);
                $fullClass = $servicesNamespace.'\\'.$seeClass;
                if (class_exists($fullClass)) {
                    return $fullClass;
                }
            }
        } catch (\ReflectionException $e) {
            // Ignore
        }

        return null;
    }

    /**
     * Remove Contract/Interface suffix from class name.
     */
    protected function removeContractSuffix(string $className): string
    {
        $suffixes = ['Contract', 'Interface'];

        foreach ($suffixes as $suffix) {
            if (Str::endsWith($className, $suffix)) {
                return Str::beforeLast($className, $suffix);
            }
        }

        return $className;
    }

    /**
     * Determine if a binding should be a singleton.
     */
    protected function shouldBeSingleton(string $contractName, string $implementationClass): bool
    {
        // Check for static property on implementation
        try {
            $reflection = new ReflectionClass($implementationClass);

            if ($reflection->hasProperty('singleton')) {
                $property = $reflection->getProperty('singleton');
                if ($property->isStatic() && $property->isPublic()) {
                    return (bool) $property->getValue();
                }
            }
        } catch (\ReflectionException $e) {
            // Ignore
        }

        // Check patterns
        foreach ($this->singletonPatterns as $pattern) {
            if (Str::contains($contractName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all discovered bindings.
     *
     * @return array<string, array{implementation: string, singleton: bool}>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get binding count summary.
     *
     * @return array{total: int, singletons: int, regular: int}
     */
    public function getSummary(): array
    {
        $singletons = 0;
        $regular = 0;

        foreach ($this->bindings as $binding) {
            if ($binding['singleton']) {
                $singletons++;
            } else {
                $regular++;
            }
        }

        return [
            'total' => count($this->bindings),
            'singletons' => $singletons,
            'regular' => $regular,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromCache(array $cachedData): void
    {
        parent::loadFromCache($cachedData);
        $this->bindings = $cachedData['bindings'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheData(): array
    {
        return array_merge(parent::getCacheData(), [
            'bindings' => $this->bindings,
        ]);
    }
}
