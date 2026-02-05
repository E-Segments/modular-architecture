<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Support;

use Esegments\ModularArchitecture\Cache\ModuleCache;
use Esegments\ModularArchitecture\Discovery\ModuleDiscovery;
use Esegments\ModularArchitecture\Discovery\ModuleStateManager;
use Illuminate\Contracts\Foundation\Application;

/**
 * Octane-specific support utilities.
 *
 * Provides methods for flushing cached state between requests
 * or when cache invalidation is needed in long-running processes.
 */
final class OctaneSupport
{
    /**
     * Flush all cached module data.
     *
     * Call this when you need to clear all in-memory caches.
     * This is automatically called when using Octane listeners.
     */
    public static function flush(Application $app): void
    {
        // Clear static cache
        ModuleCache::clearStatic();

        // Clear singleton instance caches if they exist
        if ($app->resolved(ModuleDiscovery::class)) {
            $app->make(ModuleDiscovery::class)->clear();
        }

        if ($app->resolved(ModuleStateManager::class)) {
            $app->make(ModuleStateManager::class)->reload();
        }
    }

    /**
     * Warm the module cache.
     *
     * Call this during Octane worker boot to pre-load modules.
     * This avoids the discovery overhead on the first request.
     */
    public static function warm(Application $app): void
    {
        if ($app->resolved(ModuleDiscovery::class)) {
            $app->make(ModuleDiscovery::class)->discover();
        }
    }

    /**
     * Register Octane listeners for automatic cache management.
     *
     * Call this from your Octane config or service provider:
     *
     * ```php
     * // In config/octane.php
     * 'listeners' => [
     *     WorkerStarting::class => [
     *         fn ($app) => \Esegments\ModularArchitecture\Support\OctaneSupport::warm($app),
     *     ],
     *     RequestReceived::class => [
     *         // Only if you want fresh state per request (not recommended for performance)
     *         // fn ($app) => \Esegments\ModularArchitecture\Support\OctaneSupport::flush($app),
     *     ],
     * ],
     * ```
     */
    public static function getListeners(): array
    {
        return [
            // Warm cache when worker starts
            'Laravel\Octane\Events\WorkerStarting' => [
                [self::class, 'handleWorkerStarting'],
            ],
            // Note: We don't flush on every request by default
            // as that would defeat the purpose of the static cache
        ];
    }

    /**
     * Handle worker starting event.
     */
    public static function handleWorkerStarting(object $event): void
    {
        if (property_exists($event, 'app') && $event->app instanceof Application) {
            self::warm($event->app);
        }
    }

    /**
     * Check if running under Octane.
     */
    public static function isRunningUnderOctane(): bool
    {
        return isset($_SERVER['LARAVEL_OCTANE']) && $_SERVER['LARAVEL_OCTANE'] === '1';
    }
}
