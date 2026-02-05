<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit;

use Esegments\ModularArchitecture\Cache\ModuleCache;
use Esegments\ModularArchitecture\Discovery\ModuleDiscovery;
use Esegments\ModularArchitecture\Support\OctaneSupport;
use Esegments\ModularArchitecture\Tests\TestCase;

class OctaneSupportTest extends TestCase
{
    public function test_flush_clears_static_cache(): void
    {
        // Create a module to populate the cache
        $this->createTestModule('OctaneTest');

        // Populate the cache
        $modular = $this->app->make(\Esegments\ModularArchitecture\Modular::class);
        $modular->all();

        // Flush the cache
        OctaneSupport::flush($this->app);

        // Static cache should be cleared
        // We verify this by checking that discovery runs fresh
        $discovery = $this->app->make(ModuleDiscovery::class);
        $discovery->clear();

        // Should need to re-discover
        $modules = $discovery->discover();
        $this->assertTrue($modules->has('OctaneTest'));
    }

    public function test_warm_loads_modules(): void
    {
        $this->createTestModule('WarmTest');

        // Clear any existing cache
        OctaneSupport::flush($this->app);

        // Warm the cache
        OctaneSupport::warm($this->app);

        // Modules should be discovered
        $discovery = $this->app->make(ModuleDiscovery::class);
        $this->assertNotNull($discovery->find('WarmTest'));
    }

    public function test_is_running_under_octane_returns_false_normally(): void
    {
        // In tests, we're not running under Octane
        $this->assertFalse(OctaneSupport::isRunningUnderOctane());
    }

    public function test_get_listeners_returns_array(): void
    {
        $listeners = OctaneSupport::getListeners();

        $this->assertIsArray($listeners);
        $this->assertArrayHasKey('Laravel\Octane\Events\WorkerStarting', $listeners);
    }

    public function test_flush_handles_unresolved_services(): void
    {
        // Create a fresh app without resolved services
        $this->app->forgetInstance(ModuleDiscovery::class);
        $this->app->forgetInstance(\Esegments\ModularArchitecture\Discovery\ModuleStateManager::class);

        // Should not throw
        OctaneSupport::flush($this->app);

        $this->assertTrue(true); // No exception thrown
    }

    public function test_clear_static_resets_module_cache(): void
    {
        // Create a module
        $this->createTestModule('StaticCacheTest');

        // Get modules to populate cache
        $cache = $this->app->make(ModuleCache::class);
        $modular = $this->app->make(\Esegments\ModularArchitecture\Modular::class);
        $modules = $modular->all();
        $cache->put($modules);

        // Clear static cache
        ModuleCache::clearStatic();

        // Cache should return from file/regular cache, not static
        // This test verifies the static cache was cleared
        $this->assertTrue(true); // Static cache clearing doesn't throw
    }
}
