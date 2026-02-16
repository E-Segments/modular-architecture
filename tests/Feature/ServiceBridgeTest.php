<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Bridges\Service\ServiceBridge;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class ServiceBridgeTest extends TestCase
{
    protected ServiceBridge $bridge;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bridge = $this->app->make(ServiceBridge::class);
    }

    public function test_bridge_name(): void
    {
        $this->assertEquals('service', $this->bridge->name());
    }

    public function test_service_bridge_is_always_available(): void
    {
        $this->assertTrue($this->bridge->isAvailable());
    }

    public function test_bridge_is_disabled_by_default(): void
    {
        $this->assertFalse($this->bridge->isEnabled());
    }

    public function test_bridge_can_be_enabled(): void
    {
        Config::set('modular.bridges.services.enabled', true);

        $this->assertTrue($this->bridge->isEnabled());
    }

    public function test_register_module_skips_without_contracts(): void
    {
        $modulePath = $this->createTempModule('TestModule');
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $this->assertTrue($this->bridge->getRegistered()->isEmpty());
    }

    public function test_get_bindings_returns_empty_initially(): void
    {
        $this->assertEmpty($this->bridge->getBindings());
    }

    public function test_get_summary_returns_zeros_initially(): void
    {
        $summary = $this->bridge->getSummary();

        $this->assertEquals(0, $summary['total']);
        $this->assertEquals(0, $summary['singletons']);
        $this->assertEquals(0, $summary['regular']);
    }

    public function test_cache_data_includes_bindings(): void
    {
        $cacheData = $this->bridge->getCacheData();

        $this->assertArrayHasKey('registered', $cacheData);
        $this->assertArrayHasKey('bindings', $cacheData);
    }

    protected function createTempModule(string $name): string
    {
        $path = sys_get_temp_dir().'/modular-test-'.$name.'-'.uniqid();
        @mkdir($path, 0777, true);
        file_put_contents($path.'/module.json', json_encode([
            'name' => $name,
            'version' => '1.0.0',
        ]));

        return $path;
    }

    protected function tearDown(): void
    {
        foreach (glob(sys_get_temp_dir().'/modular-test-*') as $dir) {
            $this->recursiveDelete($dir);
        }
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
