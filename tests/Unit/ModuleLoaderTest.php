<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit;

use Esegments\ModularArchitecture\Exceptions\InvalidManifestException;
use Esegments\ModularArchitecture\Module\ModuleLoader;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;

class ModuleLoaderTest extends TestCase
{
    protected ModuleLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = new ModuleLoader(
            files: new Filesystem,
        );
    }

    public function test_loads_module_from_valid_path(): void
    {
        $path = $this->createTestModule('ValidModule');

        $module = $this->loader->load($path);

        $this->assertEquals('ValidModule', $module->getName());
        $this->assertEquals('1.0.0', $module->getVersion());
        $this->assertEquals($path, $module->getPath());
    }

    public function test_throws_exception_for_missing_manifest(): void
    {
        $path = $this->getTestModulesPath().'/NonExistent';

        $this->expectException(InvalidManifestException::class);
        $this->expectExceptionMessage('not found');

        $this->loader->load($path);
    }

    public function test_loads_module_with_dependencies(): void
    {
        $path = $this->createTestModule('DependentModule', [
            'requires' => [
                'Core' => '^1.0',
                'Blog' => '^2.0',
            ],
        ]);

        $module = $this->loader->load($path);

        $this->assertEquals([
            'Core' => '^1.0',
            'Blog' => '^2.0',
        ], $module->getRequires());
    }

    public function test_loads_module_with_providers(): void
    {
        $path = $this->createTestModule('ProviderModule', [
            'providers' => [
                'Modules\\ProviderModule\\Providers\\ProviderModuleServiceProvider',
            ],
        ]);

        $module = $this->loader->load($path);

        $this->assertContains(
            'Modules\\ProviderModule\\Providers\\ProviderModuleServiceProvider',
            $module->getProviders()
        );
    }

    public function test_generates_correct_namespace(): void
    {
        $path = $this->createTestModule('TestNamespace');

        $module = $this->loader->load($path);

        $this->assertEquals('Modules\\TestNamespace', $module->getNamespace());
    }
}
