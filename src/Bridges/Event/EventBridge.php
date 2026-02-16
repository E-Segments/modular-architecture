<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Event;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;

/**
 * Event Bridge.
 *
 * Auto-discovers and registers event listeners and subscribers from enabled modules.
 *
 * Discovery paths:
 * - app/Listeners/*.php -> Event listeners
 * - app/Subscribers/*.php -> Event subscribers
 *
 * Listeners are matched to events by convention:
 * - SendOrderConfirmation listens to OrderPlaced
 * - HandlePaymentFailed listens to PaymentFailed
 */
class EventBridge extends AbstractBridge
{
    /**
     * Discovered listeners.
     *
     * @var array<string, string> Event => Listener class
     */
    protected array $listeners = [];

    /**
     * Discovered subscribers.
     *
     * @var array<string>
     */
    protected array $subscribers = [];

    public function name(): string
    {
        return 'event';
    }

    protected function configKey(): string
    {
        return 'events';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Events\\Dispatcher';
    }

    protected function componentPath(): string
    {
        return 'app/Listeners';
    }

    public function registerModule(Module $module): void
    {
        $appPath = $module->getPath().'/app';

        $discoveredListeners = $this->discoverListeners($module, $appPath.'/Listeners');
        $discoveredSubscribers = $this->discoverSubscribers($module, $appPath.'/Subscribers');

        $totalCount = count($discoveredListeners) + count($discoveredSubscribers);

        if ($totalCount === 0) {
            return;
        }

        $this->registered->put($module->getName(), [
            'listeners' => $discoveredListeners,
            'subscribers' => $discoveredSubscribers,
        ]);

        // Merge into global arrays
        foreach ($discoveredListeners as $event => $listenerClasses) {
            if (! isset($this->listeners[$event])) {
                $this->listeners[$event] = [];
            }
            $this->listeners[$event] = array_merge(
                $this->listeners[$event],
                (array) $listenerClasses
            );
        }

        $this->subscribers = array_merge($this->subscribers, $discoveredSubscribers);

        $this->log("Discovered {$module->getName()} events", [
            'listeners' => count($discoveredListeners),
            'subscribers' => count($discoveredSubscribers),
        ]);
    }

    public function boot(): void
    {
        $events = $this->app->make(Dispatcher::class);

        // Register listeners
        foreach ($this->listeners as $event => $listeners) {
            foreach ((array) $listeners as $listener) {
                if (class_exists($listener) && class_exists($event)) {
                    $events->listen($event, $listener);
                }
            }
        }

        // Register subscribers
        foreach ($this->subscribers as $subscriber) {
            if (class_exists($subscriber)) {
                $events->subscribe($subscriber);
            }
        }

        $this->log('Registered event listeners', [
            'listeners' => count($this->listeners),
            'subscribers' => count($this->subscribers),
        ]);
    }

    /**
     * Discover event listeners.
     *
     * @return array<string, array<string>>
     */
    protected function discoverListeners(Module $module, string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $files = $this->app->make(Filesystem::class);
        $listeners = [];
        $namespace = "Modules\\{$module->getName()}\\Listeners";

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

            // Try to discover the event from the handle method
            $event = $this->discoverEventFromListener($fullClass, $module);

            if ($event) {
                if (! isset($listeners[$event])) {
                    $listeners[$event] = [];
                }
                $listeners[$event][] = $fullClass;
            }
        }

        return $listeners;
    }

    /**
     * Discover the event class from a listener's handle method.
     */
    protected function discoverEventFromListener(string $listenerClass, Module $module): ?string
    {
        try {
            $reflection = new ReflectionClass($listenerClass);

            if (! $reflection->hasMethod('handle')) {
                return null;
            }

            $method = $reflection->getMethod('handle');
            $params = $method->getParameters();

            if (empty($params)) {
                return null;
            }

            $type = $params[0]->getType();

            if ($type === null) {
                return null;
            }

            if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                return $type->getName();
            }
        } catch (\ReflectionException $e) {
            // Ignore reflection errors
        }

        return null;
    }

    /**
     * Discover event subscribers.
     *
     * @return array<string>
     */
    protected function discoverSubscribers(Module $module, string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $files = $this->app->make(Filesystem::class);
        $subscribers = [];
        $namespace = "Modules\\{$module->getName()}\\Subscribers";

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

            // Check if it has a subscribe method
            if (method_exists($fullClass, 'subscribe')) {
                $subscribers[] = $fullClass;
            }
        }

        return $subscribers;
    }

    /**
     * Get all discovered listeners.
     *
     * @return array<string, array<string>>
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    /**
     * Get all discovered subscribers.
     *
     * @return array<string>
     */
    public function getSubscribers(): array
    {
        return $this->subscribers;
    }
}
