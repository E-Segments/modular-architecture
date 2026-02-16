<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Bridges\Migration\MigrationBridge;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class MigrationBridgeTest extends TestCase
{
    protected MigrationBridge $bridge;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bridge = $this->app->make(MigrationBridge::class);
    }

    public function test_bridge_name(): void
    {
        $this->assertEquals('migration', $this->bridge->name());
    }

    public function test_migration_bridge_is_always_available(): void
    {
        $this->assertTrue($this->bridge->isAvailable());
    }

    public function test_bridge_is_disabled_by_default(): void
    {
        $this->assertFalse($this->bridge->isEnabled());
    }

    public function test_bridge_can_be_enabled(): void
    {
        Config::set('modular.bridges.migrations.enabled', true);

        $this->assertTrue($this->bridge->isEnabled());
    }

    public function test_register_module_skips_without_migrations(): void
    {
        $modulePath = $this->createTempModule('TestModule');
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $this->assertTrue($this->bridge->getRegistered()->isEmpty());
    }

    public function test_register_module_with_migrations(): void
    {
        $modulePath = $this->createTempModuleWithMigrations('Products', 3);
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $registered = $this->bridge->getRegistered();
        $this->assertTrue($registered->has('Products'));
        $this->assertEquals(3, $registered->get('Products')['count']);
    }

    public function test_get_migration_paths(): void
    {
        $modulePath = $this->createTempModuleWithMigrations('Products', 2);
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $paths = $this->bridge->getMigrationPaths();
        $this->assertArrayHasKey('Products', $paths);
    }

    public function test_get_total_migration_count(): void
    {
        $modulePath1 = $this->createTempModuleWithMigrations('Products', 2);
        $modulePath2 = $this->createTempModuleWithMigrations('Orders', 3);

        $this->bridge->registerModule(Module::fromPath($modulePath1));
        $this->bridge->registerModule(Module::fromPath($modulePath2));

        $this->assertEquals(5, $this->bridge->getTotalMigrationCount());
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

    protected function createTempModuleWithMigrations(string $name, int $count): string
    {
        $path = $this->createTempModule($name);
        @mkdir($path.'/database/migrations', 0777, true);

        for ($i = 1; $i <= $count; $i++) {
            $timestamp = date('Y_m_d_His', strtotime("+{$i} seconds"));
            file_put_contents(
                $path."/database/migrations/{$timestamp}_create_table_{$i}.php",
                "<?php\n// Migration {$i}\n"
            );
        }

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
