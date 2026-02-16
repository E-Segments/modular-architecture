<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Policy;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Policy Bridge.
 *
 * Auto-discovers and registers authorization policies from enabled modules.
 *
 * Discovery path: app/Policies/*.php
 *
 * Matching convention:
 * - ProductPolicy authorizes Product model
 * - UserPolicy authorizes User model
 *
 * The policy must follow naming: {ModelName}Policy
 * The model is looked up in the same module's app/Models directory.
 */
class PolicyBridge extends AbstractBridge
{
    /**
     * Discovered policy-model pairs.
     *
     * @var array<string, string> Model class => Policy class
     */
    protected array $policies = [];

    public function name(): string
    {
        return 'policy';
    }

    protected function configKey(): string
    {
        return 'policies';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Auth\\Access\\Gate';
    }

    protected function componentPath(): string
    {
        return 'app/Policies';
    }

    public function registerModule(Module $module): void
    {
        $policiesPath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($policiesPath)) {
            return;
        }

        $discovered = $this->discoverPolicies($module, $policiesPath);

        if (empty($discovered)) {
            return;
        }

        $this->registered->put($module->getName(), [
            'path' => $policiesPath,
            'policies' => $discovered,
        ]);

        $this->policies = array_merge($this->policies, $discovered);

        $this->log("Discovered {$module->getName()} policies", [
            'count' => count($discovered),
        ]);
    }

    public function boot(): void
    {
        if (empty($this->policies)) {
            return;
        }

        $gate = $this->app->make(Gate::class);

        foreach ($this->policies as $modelClass => $policyClass) {
            if (class_exists($policyClass) && class_exists($modelClass)) {
                $gate->policy($modelClass, $policyClass);
            }
        }

        $this->log('Registered authorization policies', [
            'count' => count($this->policies),
        ]);
    }

    /**
     * Discover policies and match them to models.
     *
     * @return array<string, string> Model class => Policy class
     */
    protected function discoverPolicies(Module $module, string $policiesPath): array
    {
        $files = $this->app->make(Filesystem::class);
        $policies = [];
        $namespace = "Modules\\{$module->getName()}\\Policies";
        $modelNamespace = "Modules\\{$module->getName()}\\Models";

        foreach ($files->allFiles($policiesPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($policiesPath.'/', '', $file->getPathname());
            $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $policyClass = $namespace.'\\'.$className;

            if (! class_exists($policyClass)) {
                continue;
            }

            // Try to match to a model
            $modelClass = $this->resolveModelClass($policyClass, $modelNamespace, $className);

            if ($modelClass && class_exists($modelClass)) {
                $policies[$modelClass] = $policyClass;
            }
        }

        return $policies;
    }

    /**
     * Resolve the model class for a policy.
     */
    protected function resolveModelClass(string $policyClass, string $modelNamespace, string $className): ?string
    {
        // Remove "Policy" suffix to get model name
        if (! Str::endsWith($className, 'Policy')) {
            return null;
        }

        $modelName = Str::beforeLast($className, 'Policy');

        // Handle nested policies (e.g., Admin/UserPolicy -> Admin/User)
        $modelClass = $modelNamespace.'\\'.$modelName;

        if (class_exists($modelClass)) {
            return $modelClass;
        }

        // Try without nested path
        $simpleModelName = class_basename($modelName);
        $simpleModelClass = $modelNamespace.'\\'.$simpleModelName;

        if (class_exists($simpleModelClass)) {
            return $simpleModelClass;
        }

        return null;
    }

    /**
     * Get all discovered policies.
     *
     * @return array<string, string>
     */
    public function getPolicies(): array
    {
        return $this->policies;
    }
}
