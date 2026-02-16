<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Bridges\Translation\TranslationBridge;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class TranslationBridgeTest extends TestCase
{
    protected TranslationBridge $bridge;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bridge = $this->app->make(TranslationBridge::class);
    }

    public function test_bridge_name(): void
    {
        $this->assertEquals('translation', $this->bridge->name());
    }

    public function test_translation_bridge_is_always_available(): void
    {
        $this->assertTrue($this->bridge->isAvailable());
    }

    public function test_bridge_is_disabled_by_default(): void
    {
        $this->assertFalse($this->bridge->isEnabled());
    }

    public function test_bridge_can_be_enabled(): void
    {
        Config::set('modular.bridges.translations.enabled', true);

        $this->assertTrue($this->bridge->isEnabled());
    }

    public function test_register_module_skips_without_translations(): void
    {
        $modulePath = $this->createTempModule('TestModule');
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $this->assertTrue($this->bridge->getRegistered()->isEmpty());
    }

    public function test_register_module_with_php_translations(): void
    {
        $modulePath = $this->createTempModuleWithTranslations('Products', ['en']);
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $registered = $this->bridge->getRegistered();
        $this->assertTrue($registered->has('Products'));
        $this->assertEquals('products', $registered->get('Products')['namespace']);
    }

    public function test_register_module_with_json_translations(): void
    {
        $modulePath = $this->createTempModuleWithJsonTranslations('Products');
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $registered = $this->bridge->getRegistered();
        $this->assertTrue($registered->has('Products'));
        $this->assertNotEmpty($registered->get('Products')['json_files']);
    }

    public function test_get_lang_paths(): void
    {
        $modulePath = $this->createTempModuleWithTranslations('Products', ['en']);
        $module = Module::fromPath($modulePath);

        $this->bridge->registerModule($module);

        $paths = $this->bridge->getLangPaths();
        $this->assertArrayHasKey('products', $paths);
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

    protected function createTempModuleWithTranslations(string $name, array $locales): string
    {
        $path = $this->createTempModule($name);

        foreach ($locales as $locale) {
            @mkdir($path.'/lang/'.$locale, 0777, true);
            file_put_contents(
                $path.'/lang/'.$locale.'/messages.php',
                "<?php\nreturn ['greeting' => 'Hello'];\n"
            );
        }

        return $path;
    }

    protected function createTempModuleWithJsonTranslations(string $name): string
    {
        $path = $this->createTempModule($name);
        @mkdir($path.'/lang', 0777, true);
        file_put_contents(
            $path.'/lang/en.json',
            json_encode(['greeting' => 'Hello'])
        );

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
