<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleDisable;
use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleEnable;
use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleInstall;
use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleMigrate;
use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleSeed;
use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleUninstall;
use Esegments\ModularArchitecture\Extensions\Points\ModuleDisabled;
use Esegments\ModularArchitecture\Extensions\Points\ModuleEnabled;
use Esegments\ModularArchitecture\Extensions\Points\ModuleInstalled;
use Esegments\ModularArchitecture\Extensions\Points\ModuleMigrated;
use Esegments\ModularArchitecture\Extensions\Points\ModuleSeeded;
use Esegments\ModularArchitecture\Extensions\Points\ModuleUninstalled;
use Esegments\ModularArchitecture\Tests\TestCase;

class ExtensionPointsTest extends TestCase
{
    // BeforeModuleInstall tests
    public function test_before_module_install_can_add_errors(): void
    {
        $point = new BeforeModuleInstall('owner/repo', 'v1.0.0');

        $this->assertFalse($point->hasErrors());

        $point->addError('Installation blocked');

        $this->assertTrue($point->hasErrors());
        $this->assertContains('Installation blocked', $point->errors);
    }

    public function test_before_module_install_can_be_interrupted(): void
    {
        $point = new BeforeModuleInstall('owner/repo');

        $this->assertFalse($point->wasInterrupted());

        $point->interrupt();

        $this->assertTrue($point->wasInterrupted());
    }

    public function test_before_module_install_has_correct_properties(): void
    {
        $point = new BeforeModuleInstall('owner/repo', 'v2.0.0');

        $this->assertEquals('owner/repo', $point->source);
        $this->assertEquals('v2.0.0', $point->version);
    }

    // ModuleInstalled tests
    public function test_module_installed_has_correct_properties(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new ModuleInstalled(
            module: $module,
            moduleName: 'Blog',
            source: 'owner/blog-module',
            version: 'v1.0.0',
            path: $path,
        );

        $this->assertEquals('Blog', $point->moduleName);
        $this->assertEquals('owner/blog-module', $point->source);
        $this->assertEquals('v1.0.0', $point->version);
        $this->assertEquals($path, $point->path);
    }

    // BeforeModuleUninstall tests
    public function test_before_module_uninstall_can_add_errors(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new BeforeModuleUninstall($module, 'Blog', false);

        $this->assertFalse($point->hasErrors());

        $point->addError('Uninstall blocked');

        $this->assertTrue($point->hasErrors());
    }

    public function test_before_module_uninstall_can_be_interrupted(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new BeforeModuleUninstall($module, 'Blog', true);

        $this->assertFalse($point->wasInterrupted());
        $this->assertTrue($point->force);

        $point->interrupt();

        $this->assertTrue($point->wasInterrupted());
    }

    // ModuleUninstalled tests
    public function test_module_uninstalled_has_correct_properties(): void
    {
        $point = new ModuleUninstalled('Blog', '/path/to/module');

        $this->assertEquals('Blog', $point->moduleName);
        $this->assertEquals('/path/to/module', $point->path);
    }

    // BeforeModuleMigrate tests
    public function test_before_module_migrate_can_add_errors(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new BeforeModuleMigrate($module, 'Blog', true, true);

        $this->assertTrue($point->fresh);
        $this->assertTrue($point->seed);
        $this->assertFalse($point->hasErrors());

        $point->addError('Migration blocked');

        $this->assertTrue($point->hasErrors());
    }

    public function test_before_module_migrate_can_be_interrupted(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new BeforeModuleMigrate($module, 'Blog');

        $point->interrupt();

        $this->assertTrue($point->wasInterrupted());
    }

    // ModuleMigrated tests
    public function test_module_migrated_has_correct_properties(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new ModuleMigrated($module, 'Blog', 5);

        $this->assertEquals('Blog', $point->moduleName);
        $this->assertEquals(5, $point->migrationCount);
    }

    // BeforeModuleSeed tests
    public function test_before_module_seed_can_add_errors(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new BeforeModuleSeed($module, 'Blog', 'DatabaseSeeder');

        $this->assertEquals('DatabaseSeeder', $point->seederClass);
        $this->assertFalse($point->hasErrors());

        $point->addError('Seeding blocked');

        $this->assertTrue($point->hasErrors());
    }

    public function test_before_module_seed_can_be_interrupted(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new BeforeModuleSeed($module, 'Blog');

        $point->interrupt();

        $this->assertTrue($point->wasInterrupted());
    }

    // ModuleSeeded tests
    public function test_module_seeded_has_correct_properties(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new ModuleSeeded($module, 'Blog', 'DatabaseSeeder');

        $this->assertEquals('Blog', $point->moduleName);
        $this->assertEquals('DatabaseSeeder', $point->seederClass);
    }

    // BeforeModuleEnable tests
    public function test_before_module_enable_can_add_errors(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new BeforeModuleEnable($module, 'Blog');

        $this->assertFalse($point->hasErrors());

        $point->addError('Enable blocked');

        $this->assertTrue($point->hasErrors());
        $this->assertContains('Enable blocked', $point->errors);
    }

    public function test_before_module_enable_can_be_interrupted(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new BeforeModuleEnable($module, 'Blog');

        $this->assertFalse($point->wasInterrupted());

        $point->interrupt();

        $this->assertTrue($point->wasInterrupted());
    }

    // ModuleEnabled tests
    public function test_module_enabled_has_correct_properties(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new ModuleEnabled($module, 'Blog');

        $this->assertEquals('Blog', $point->moduleName);
        $this->assertSame($module, $point->module);
    }

    // BeforeModuleDisable tests
    public function test_before_module_disable_can_add_errors(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new BeforeModuleDisable($module, 'Blog');

        $this->assertFalse($point->hasErrors());

        $point->addError('Disable blocked');

        $this->assertTrue($point->hasErrors());
    }

    // ModuleDisabled tests
    public function test_module_disabled_has_correct_properties(): void
    {
        $path = $this->createTestModule('Blog');
        $module = \Esegments\ModularArchitecture\Facades\Modular::find('Blog');

        $point = new ModuleDisabled($module, 'Blog');

        $this->assertEquals('Blog', $point->moduleName);
        $this->assertSame($module, $point->module);
    }
}
