<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Bridges\Blade\BladeBridge;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class BladeBridgeTest extends TestCase
{
    protected BladeBridge $bridge;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bridge = $this->app->make(BladeBridge::class);
    }

    public function test_bridge_name(): void
    {
        $this->assertEquals('blade', $this->bridge->name());
    }

    public function test_blade_is_always_available(): void
    {
        $this->assertTrue($this->bridge->isAvailable());
    }

    public function test_bridge_is_disabled_by_default(): void
    {
        $this->assertFalse($this->bridge->isEnabled());
    }

    public function test_bridge_can_be_enabled(): void
    {
        Config::set('modular.bridges.blade.enabled', true);

        $this->assertTrue($this->bridge->isEnabled());
    }

    public function test_register_module_skips_without_views(): void
    {
        $modulePath = $this->createTempModuleWithoutViews('TestModule');
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $this->assertTrue($this->bridge->getRegistered()->isEmpty());
    }

    public function test_register_module_with_views(): void
    {
        $modulePath = $this->createTempModuleWithViews('Products');
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $registered = $this->bridge->getRegistered();
        $this->assertTrue($registered->has('Products'));
        $this->assertNotNull($registered->get('Products')['views_path']);
    }

    public function test_view_paths_are_registered_on_boot(): void
    {
        $modulePath = $this->createTempModuleWithViews('Products');
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);
        $this->bridge->boot();

        $viewPaths = $this->bridge->getViewPaths();
        $this->assertArrayHasKey('products', $viewPaths);
    }

    /**
     * Create a temporary module without views directory.
     */
    protected function createTempModuleWithoutViews(string $name): string
    {
        $path = sys_get_temp_dir().'/modular-test-'.$name.'-'.uniqid();

        @mkdir($path, 0777, true);

        // Create module.json
        file_put_contents($path.'/module.json', json_encode([
            'name' => $name,
            'version' => '1.0.0',
        ]));

        return $path;
    }

    /**
     * Create a temporary module with views directory.
     */
    protected function createTempModuleWithViews(string $name): string
    {
        $path = sys_get_temp_dir().'/modular-test-'.$name.'-'.uniqid();

        @mkdir($path.'/resources/views', 0777, true);
        file_put_contents($path.'/resources/views/index.blade.php', '<h1>Test</h1>');

        // Create module.json
        file_put_contents($path.'/module.json', json_encode([
            'name' => $name,
            'version' => '1.0.0',
        ]));

        return $path;
    }

    protected function tearDown(): void
    {
        // Cleanup temp directories
        $patterns = [
            sys_get_temp_dir().'/modular-test-*',
        ];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) as $dir) {
                $this->recursiveDelete($dir);
            }
        }

        parent::tearDown();
    }

    protected function recursiveDelete(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (is_dir($dir.'/'.$object)) {
                        $this->recursiveDelete($dir.'/'.$object);
                    } else {
                        unlink($dir.'/'.$object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
