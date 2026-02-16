<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Bridges\Route\RouteBridge;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class RouteBridgeTest extends TestCase
{
    protected RouteBridge $bridge;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bridge = $this->app->make(RouteBridge::class);
    }

    public function test_bridge_name(): void
    {
        $this->assertEquals('route', $this->bridge->name());
    }

    public function test_route_bridge_is_always_available(): void
    {
        $this->assertTrue($this->bridge->isAvailable());
    }

    public function test_bridge_is_disabled_by_default(): void
    {
        $this->assertFalse($this->bridge->isEnabled());
    }

    public function test_bridge_can_be_enabled(): void
    {
        Config::set('modular.bridges.routes.enabled', true);

        $this->assertTrue($this->bridge->isEnabled());
    }

    public function test_register_module_skips_without_routes(): void
    {
        $modulePath = $this->createTempModuleWithoutRoutes('TestModule');
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $this->assertTrue($this->bridge->getRegistered()->isEmpty());
    }

    public function test_register_module_with_routes(): void
    {
        $modulePath = $this->createTempModuleWithRoutes('Products', ['web', 'api']);
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $registered = $this->bridge->getRegistered();
        $this->assertTrue($registered->has('Products'));

        $routeFiles = $this->bridge->getRouteFiles();
        $this->assertArrayHasKey('Products', $routeFiles);
        $this->assertArrayHasKey('web', $routeFiles['Products']);
        $this->assertArrayHasKey('api', $routeFiles['Products']);
    }

    public function test_config_options_for_routes(): void
    {
        Config::set('modular.bridges.routes.enabled', true);
        Config::set('modular.bridges.routes.prefix_web', false);
        Config::set('modular.bridges.routes.api_prefix', 'v1');

        $this->assertFalse(config('modular.bridges.routes.prefix_web'));
        $this->assertEquals('v1', config('modular.bridges.routes.api_prefix'));
    }

    /**
     * Create a temporary module without routes directory.
     */
    protected function createTempModuleWithoutRoutes(string $name): string
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
     * Create a temporary module with routes directory.
     */
    protected function createTempModuleWithRoutes(string $name, array $types = ['web']): string
    {
        $path = sys_get_temp_dir().'/modular-test-'.$name.'-'.uniqid();

        @mkdir($path.'/routes', 0777, true);

        foreach ($types as $type) {
            file_put_contents($path.'/routes/'.$type.'.php', "<?php\n// {$type} routes\n");
        }

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
        foreach (glob(sys_get_temp_dir().'/modular-test-*') as $dir) {
            $this->recursiveDelete($dir);
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
