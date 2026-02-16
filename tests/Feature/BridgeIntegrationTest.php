<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Bridges\BridgeManager;
use Esegments\ModularArchitecture\Bridges\Cache\BridgeCache;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;

class BridgeIntegrationTest extends TestCase
{
    protected BridgeManager $manager;

    protected string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->app->make(BridgeManager::class);
        $this->cachePath = sys_get_temp_dir().'/modular-integration-test-'.uniqid();
        @mkdir($this->cachePath, 0777, true);
    }

    public function test_all_bridges_are_registered(): void
    {
        $bridges = $this->manager->all();

        $this->assertGreaterThanOrEqual(15, $bridges->count());

        // Verify key bridges exist
        $this->assertNotNull($bridges->get('livewire'));
        $this->assertNotNull($bridges->get('filament'));
        $this->assertNotNull($bridges->get('routes'));
        $this->assertNotNull($bridges->get('blade'));
        $this->assertNotNull($bridges->get('events'));
        $this->assertNotNull($bridges->get('migrations'));
        $this->assertNotNull($bridges->get('translations'));
        $this->assertNotNull($bridges->get('observers'));
        $this->assertNotNull($bridges->get('policies'));
        $this->assertNotNull($bridges->get('commands'));
        $this->assertNotNull($bridges->get('middleware'));
        $this->assertNotNull($bridges->get('services'));
        $this->assertNotNull($bridges->get('config'));
        $this->assertNotNull($bridges->get('assets'));
    }

    public function test_enabled_bridges_are_filtered(): void
    {
        // Enable specific bridges
        Config::set('modular.bridges.blade.enabled', true);
        Config::set('modular.bridges.migrations.enabled', true);

        $enabled = $this->manager->enabled();

        $this->assertEquals(2, $enabled->count());
    }

    public function test_register_module_calls_all_enabled_bridges(): void
    {
        Config::set('modular.bridges.blade.enabled', true);
        Config::set('modular.bridges.routes.enabled', true);

        $modulePath = $this->createTempModuleWithAll('TestModule');
        $module = Module::fromPath($modulePath);

        $this->manager->registerModule($module);

        // Check that enabled bridges received the module
        $bladeBridge = $this->manager->get('blade');
        $routeBridge = $this->manager->get('routes');

        $this->assertTrue($bladeBridge->getRegistered()->has('TestModule'));
        $this->assertTrue($routeBridge->getRegistered()->has('TestModule'));
    }

    public function test_boot_only_runs_once(): void
    {
        $this->assertFalse($this->manager->isBooted());

        $this->manager->boot();
        $this->assertTrue($this->manager->isBooted());

        // Should not throw or change state
        $this->manager->boot();
        $this->assertTrue($this->manager->isBooted());
    }

    public function test_cache_integration(): void
    {
        $cache = new BridgeCache(
            cache: $this->app->make(CacheRepository::class),
            files: $this->app->make(Filesystem::class),
            cachePath: $this->cachePath,
            ttl: 3600,
            enabled: true,
            driver: 'file',
        );

        $this->manager->setCache($cache);

        // Register a module
        Config::set('modular.bridges.blade.enabled', true);
        $modulePath = $this->createTempModuleWithViews('CachedModule');
        $module = Module::fromPath($modulePath);

        $this->manager->registerModule($module);

        // Save to cache
        $this->manager->saveToCache();

        // Verify cache has data
        $this->assertTrue($cache->has());

        // Create a new manager and load from cache
        $newManager = new BridgeManager($this->app);
        $newManager->setCache($cache);

        // Re-register bridges (simulating fresh boot)
        foreach ($this->manager->all() as $name => $bridge) {
            $newManager->register($name, $bridge);
        }

        // Load from cache
        $loaded = $newManager->loadFromCache();
        $this->assertTrue($loaded);
        $this->assertTrue($newManager->wasLoadedFromCache());
    }

    public function test_status_returns_all_bridge_info(): void
    {
        $status = $this->manager->status();

        $this->assertIsArray($status);
        $this->assertNotEmpty($status);

        foreach ($status as $bridgeName => $info) {
            $this->assertArrayHasKey('available', $info);
            $this->assertArrayHasKey('enabled', $info);
            $this->assertArrayHasKey('components', $info);
        }
    }

    public function test_bridges_have_detailed_status(): void
    {
        $bladeBridge = $this->manager->get('blade');

        if ($bladeBridge instanceof AbstractBridge) {
            $status = $bladeBridge->getStatus();

            $this->assertArrayHasKey('name', $status);
            $this->assertArrayHasKey('available', $status);
            $this->assertArrayHasKey('enabled', $status);
            $this->assertArrayHasKey('config_key', $status);
            $this->assertArrayHasKey('required_class', $status);
            $this->assertArrayHasKey('component_path', $status);
            $this->assertArrayHasKey('modules_count', $status);
            $this->assertArrayHasKey('from_cache', $status);
        }
    }

    protected function createTempModuleWithAll(string $name): string
    {
        $path = sys_get_temp_dir().'/modular-test-'.$name.'-'.uniqid();

        // Create module.json
        @mkdir($path, 0777, true);
        file_put_contents($path.'/module.json', json_encode([
            'name' => $name,
            'version' => '1.0.0',
        ]));

        // Create views
        @mkdir($path.'/resources/views', 0777, true);
        file_put_contents($path.'/resources/views/index.blade.php', '<h1>Test</h1>');

        // Create routes
        @mkdir($path.'/routes', 0777, true);
        file_put_contents($path.'/routes/web.php', "<?php\n// routes\n");

        return $path;
    }

    protected function createTempModuleWithViews(string $name): string
    {
        $path = sys_get_temp_dir().'/modular-test-'.$name.'-'.uniqid();

        @mkdir($path.'/resources/views', 0777, true);
        file_put_contents($path.'/module.json', json_encode([
            'name' => $name,
            'version' => '1.0.0',
        ]));
        file_put_contents($path.'/resources/views/index.blade.php', '<h1>Test</h1>');

        return $path;
    }

    protected function tearDown(): void
    {
        foreach (glob(sys_get_temp_dir().'/modular-test-*') as $dir) {
            $this->recursiveDelete($dir);
        }
        $this->recursiveDelete($this->cachePath);
        parent::tearDown();
    }

    protected function recursiveDelete(string $dir): void
    {
        if (is_dir($dir)) {
            foreach (scandir($dir) as $item) {
                if ($item !== '.' && $item !== '..') {
                    $path = $dir.'/'.$item;
                    is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
                }
            }
            rmdir($dir);
        }
    }
}
