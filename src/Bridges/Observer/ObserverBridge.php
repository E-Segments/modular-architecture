<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Observer;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Observer Bridge.
 *
 * Auto-discovers and registers model observers from enabled modules.
 *
 * Discovery path: app/Observers/*.php
 *
 * Matching convention:
 * - ProductObserver observes Product model
 * - UserObserver observes User model
 *
 * The observer must follow naming: {ModelName}Observer
 * The model is looked up in the same module's app/Models directory.
 */
class ObserverBridge extends AbstractBridge
{
    /**
     * Discovered observer-model pairs.
     *
     * @var array<string, string> Observer class => Model class
     */
    protected array $observers = [];

    public function name(): string
    {
        return 'observer';
    }

    protected function configKey(): string
    {
        return 'observers';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Database\\Eloquent\\Model';
    }

    protected function componentPath(): string
    {
        return 'app/Observers';
    }

    public function registerModule(Module $module): void
    {
        $observersPath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($observersPath)) {
            return;
        }

        $discovered = $this->discoverObservers($module, $observersPath);

        if (empty($discovered)) {
            return;
        }

        $this->registered->put($module->getName(), [
            'path' => $observersPath,
            'observers' => $discovered,
        ]);

        $this->observers = array_merge($this->observers, $discovered);

        $this->log("Discovered {$module->getName()} observers", [
            'count' => count($discovered),
        ]);
    }

    public function boot(): void
    {
        if (empty($this->observers)) {
            return;
        }

        foreach ($this->observers as $observerClass => $modelClass) {
            if (class_exists($observerClass) && class_exists($modelClass)) {
                $modelClass::observe($observerClass);
            }
        }

        $this->log('Registered model observers', [
            'count' => count($this->observers),
        ]);
    }

    /**
     * Discover observers and match them to models.
     *
     * @return array<string, string> Observer class => Model class
     */
    protected function discoverObservers(Module $module, string $observersPath): array
    {
        $files = $this->app->make(Filesystem::class);
        $observers = [];
        $namespace = "Modules\\{$module->getName()}\\Observers";
        $modelNamespace = "Modules\\{$module->getName()}\\Models";

        foreach ($files->allFiles($observersPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($observersPath.'/', '', $file->getPathname());
            $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $observerClass = $namespace.'\\'.$className;

            if (! class_exists($observerClass)) {
                continue;
            }

            // Try to match to a model
            $modelClass = $this->resolveModelClass($observerClass, $modelNamespace, $className);

            if ($modelClass && class_exists($modelClass)) {
                $observers[$observerClass] = $modelClass;
            }
        }

        return $observers;
    }

    /**
     * Resolve the model class for an observer.
     */
    protected function resolveModelClass(string $observerClass, string $modelNamespace, string $className): ?string
    {
        // Remove "Observer" suffix to get model name
        if (! Str::endsWith($className, 'Observer')) {
            return null;
        }

        $modelName = Str::beforeLast($className, 'Observer');

        // Handle nested observers (e.g., Admin/UserObserver -> Admin/User)
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
     * Get all discovered observers.
     *
     * @return array<string, string>
     */
    public function getObservers(): array
    {
        return $this->observers;
    }
}
