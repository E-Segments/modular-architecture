<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Bridges\BridgeManager;
use Esegments\ModularArchitecture\Bridges\Config\ConfigBridge;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class ConfigBridgeTest extends TestCase
{
    protected BridgeManager $manager;

    protected ConfigBridge $bridge;

    protected string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->app->make(BridgeManager::class);
        $this->bridge = $this->manager->get('config');
        $this->tempPath = sys_get_temp_dir().'/modular-config-test-'.uniqid();
        @mkdir($this->tempPath, 0777, true);
    }

    public function test_config_bridge_is_registered(): void
    {
        $this->assertNotNull($this->bridge);
        $this->assertInstanceOf(ConfigBridge::class, $this->bridge);
    }

    public function test_bridge_has_correct_name(): void
    {
        $this->assertEquals('config', $this->bridge->name());
    }

    public function test_bridge_is_available(): void
    {
        $this->assertTrue($this->bridge->isAvailable());
    }

    public function test_bridge_is_disabled_by_default(): void
    {
        $this->assertFalse($this->bridge->isEnabled());
    }

    public function test_bridge_can_be_enabled(): void
    {
        Config::set('modular.bridges.config.enabled', true);

        $this->assertTrue($this->bridge->isEnabled());
    }

    public function test_discovers_main_config_file(): void
    {
        $modulePath = $this->createModuleWithConfig('TestModule', [
            'config.php' => ['key' => 'value'],
        ]);

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);

        $registered = $this->bridge->getRegistered();
        $this->assertTrue($registered->has('TestModule'));

        $moduleData = $registered->get('TestModule');
        $this->assertArrayHasKey('files', $moduleData);
        $this->assertArrayHasKey('main', $moduleData['files']);
    }

    public function test_discovers_alias_config_file(): void
    {
        // Alias defaults to lowercase name: "testmodule"
        $modulePath = $this->createModuleWithConfig('TestModule', [
            'testmodule.php' => ['feature' => 'enabled'],
        ]);

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);

        $registered = $this->bridge->getRegistered();
        $this->assertTrue($registered->has('TestModule'));

        $moduleData = $registered->get('TestModule');
        $this->assertArrayHasKey('alias', $moduleData['files']);
    }

    public function test_discovers_environment_config_file(): void
    {
        $modulePath = $this->createModuleWithConfig('TestModule', [
            'config.php' => ['key' => 'value'],
            'testing.php' => ['key' => 'testing_value'],
        ]);

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);

        $registered = $this->bridge->getRegistered();
        $moduleData = $registered->get('TestModule');

        $this->assertArrayHasKey('env_testing', $moduleData['files']);
    }

    public function test_skips_module_without_config_directory(): void
    {
        $modulePath = $this->createModuleWithoutConfig('NoConfigModule');

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);

        $registered = $this->bridge->getRegistered();
        $this->assertFalse($registered->has('NoConfigModule'));
    }

    public function test_boots_and_merges_config(): void
    {
        Config::set('modular.bridges.config.enabled', true);

        $modulePath = $this->createModuleWithConfig('TestModule', [
            'config.php' => [
                'feature_enabled' => true,
                'max_items' => 100,
            ],
        ]);

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);
        $this->bridge->boot();

        // Config should be merged under the module namespace
        $this->assertTrue(Config::get('testmodule.feature_enabled'));
        $this->assertEquals(100, Config::get('testmodule.max_items'));
    }

    public function test_deep_merges_nested_arrays(): void
    {
        Config::set('modular.bridges.config.enabled', true);

        // Set some existing config
        Config::set('testmodule', [
            'settings' => [
                'option_a' => true,
                'option_b' => false,
            ],
        ]);

        $modulePath = $this->createModuleWithConfig('TestModule', [
            'config.php' => [
                'settings' => [
                    'option_b' => true, // Override
                    'option_c' => 'new', // Add new
                ],
            ],
        ]);

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);
        $this->bridge->boot();

        $settings = Config::get('testmodule.settings');
        $this->assertTrue($settings['option_a']); // Preserved
        $this->assertTrue($settings['option_b']); // Overridden
        $this->assertEquals('new', $settings['option_c']); // Added
    }

    public function test_environment_config_overrides_main(): void
    {
        Config::set('modular.bridges.config.enabled', true);

        $modulePath = $this->createModuleWithConfig('TestModule', [
            'config.php' => [
                'debug' => false,
                'api_url' => 'https://production.example.com',
            ],
            'testing.php' => [
                'debug' => true,
                'api_url' => 'https://testing.example.com',
            ],
        ]);

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);
        $this->bridge->boot();

        // Testing environment config should override
        $this->assertTrue(Config::get('testmodule.debug'));
        $this->assertEquals('https://testing.example.com', Config::get('testmodule.api_url'));
    }

    public function test_get_config_files_returns_all_discovered(): void
    {
        $modulePath = $this->createModuleWithConfig('TestModule', [
            'config.php' => ['key' => 'value'],
            'testing.php' => ['key' => 'test'],
        ]);

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);

        $configFiles = $this->bridge->getConfigFiles();
        $this->assertArrayHasKey('testmodule', $configFiles);
    }

    public function test_has_config_returns_correct_status(): void
    {
        $modulePath = $this->createModuleWithConfig('TestModule', [
            'config.php' => ['key' => 'value'],
        ]);

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);

        $this->assertTrue($this->bridge->hasConfig('testmodule'));
        $this->assertFalse($this->bridge->hasConfig('non_existent'));
    }

    public function test_get_merged_config(): void
    {
        Config::set('modular.bridges.config.enabled', true);

        $modulePath = $this->createModuleWithConfig('TestModule', [
            'config.php' => ['key' => 'value'],
        ]);

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);
        $this->bridge->boot();

        $merged = $this->bridge->getMergedConfig('testmodule');
        $this->assertEquals(['key' => 'value'], $merged);
    }

    public function test_get_status_includes_config_info(): void
    {
        $modulePath = $this->createModuleWithConfig('TestModule', [
            'config.php' => ['key' => 'value'],
        ]);

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);

        $status = $this->bridge->getStatus();

        $this->assertArrayHasKey('name', $status);
        $this->assertArrayHasKey('config_namespaces', $status);
        $this->assertArrayHasKey('total_config_files', $status);
        $this->assertEquals('config', $status['name']);
    }

    public function test_cache_data_includes_config_files(): void
    {
        $modulePath = $this->createModuleWithConfig('TestModule', [
            'config.php' => ['key' => 'value'],
        ]);

        $module = Module::fromPath($modulePath);
        $this->bridge->registerModule($module);
        $this->bridge->boot();

        $cacheData = $this->bridge->getCacheData();

        $this->assertArrayHasKey('registered', $cacheData);
        $this->assertArrayHasKey('config_files', $cacheData);
        $this->assertArrayHasKey('merged_configs', $cacheData);
    }

    public function test_load_from_cache_restores_state(): void
    {
        $cacheData = [
            'registered' => [
                'TestModule' => [
                    'path' => '/path/to/config',
                    'namespace' => 'testmodule',
                    'files' => ['main' => '/path/to/config/config.php'],
                ],
            ],
            'config_files' => [
                'testmodule' => ['main' => '/path/to/config/config.php'],
            ],
            'merged_configs' => [
                'testmodule' => ['key' => 'value'],
            ],
        ];

        $this->bridge->loadFromCache($cacheData);

        $this->assertTrue($this->bridge->wasLoadedFromCache());
        $this->assertTrue($this->bridge->getRegistered()->has('TestModule'));
        $this->assertTrue($this->bridge->hasConfig('testmodule'));
        $this->assertEquals(['key' => 'value'], $this->bridge->getMergedConfig('testmodule'));
    }

    protected function createModuleWithConfig(string $name, array $configs): string
    {
        $path = $this->tempPath.'/'.$name;
        $configPath = $path.'/config';

        @mkdir($configPath, 0777, true);

        // Create module.json
        file_put_contents($path.'/module.json', json_encode([
            'name' => $name,
            'version' => '1.0.0',
        ]));

        // Create config files
        foreach ($configs as $filename => $content) {
            $code = '<?php'."\n\n".'return '.var_export($content, true).';'."\n";
            file_put_contents($configPath.'/'.$filename, $code);
        }

        return $path;
    }

    protected function createModuleWithoutConfig(string $name): string
    {
        $path = $this->tempPath.'/'.$name;

        @mkdir($path, 0777, true);

        file_put_contents($path.'/module.json', json_encode([
            'name' => $name,
            'version' => '1.0.0',
        ]));

        return $path;
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->tempPath);
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
