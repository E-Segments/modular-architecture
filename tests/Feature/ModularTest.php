<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Facades\Modular;
use Esegments\ModularArchitecture\Tests\TestCase;

class ModularTest extends TestCase
{
    public function test_can_discover_modules(): void
    {
        $this->createTestModule('Blog');
        $this->createTestModule('Products');

        $modules = Modular::all();

        $this->assertCount(2, $modules);
        $this->assertTrue($modules->hasModule('Blog'));
        $this->assertTrue($modules->hasModule('Products'));
    }

    public function test_can_find_module_by_name(): void
    {
        $this->createTestModule('Blog', [
            'version' => '2.0.0',
            'description' => 'Test blog module',
        ]);

        $module = Modular::find('Blog');

        $this->assertNotNull($module);
        $this->assertEquals('Blog', $module->getName());
        $this->assertEquals('2.0.0', $module->getVersion());
        $this->assertEquals('Test blog module', $module->getDescription());
    }

    public function test_returns_null_for_nonexistent_module(): void
    {
        $module = Modular::find('NonExistent');

        $this->assertNull($module);
    }

    public function test_can_check_module_exists(): void
    {
        $this->createTestModule('Blog');

        $this->assertTrue(Modular::exists('Blog'));
        $this->assertFalse(Modular::exists('NonExistent'));
    }

    public function test_can_enable_and_disable_module(): void
    {
        $this->createTestModule('Blog');

        // Module should be enabled by default
        $this->assertTrue(Modular::isEnabled('Blog'));

        // Disable
        Modular::disable('Blog');
        Modular::refresh();
        $this->assertFalse(Modular::isEnabled('Blog'));

        // Enable
        Modular::enable('Blog');
        Modular::refresh();
        $this->assertTrue(Modular::isEnabled('Blog'));
    }

    public function test_can_get_enabled_modules(): void
    {
        $this->createTestModule('Blog');
        $this->createTestModule('Products');
        Modular::refresh(); // Refresh to discover new modules

        Modular::disable('Products');

        $enabled = Modular::enabled();

        $this->assertCount(1, $enabled);
        $this->assertTrue($enabled->hasModule('Blog'));
        $this->assertFalse($enabled->hasModule('Products'));
    }

    public function test_can_get_disabled_modules(): void
    {
        $this->createTestModule('Blog');
        $this->createTestModule('Products');
        Modular::refresh(); // Refresh to discover new modules

        Modular::disable('Products');

        $disabled = Modular::disabled();

        $this->assertCount(1, $disabled);
        $this->assertTrue($disabled->hasModule('Products'));
    }

    public function test_can_validate_module(): void
    {
        $this->createTestModule('Blog');
        Modular::refresh();

        $result = Modular::validate('Blog');

        $this->assertTrue($result->isValid());
    }

    public function test_validates_missing_dependencies(): void
    {
        $this->createTestModule('Blog', [
            'requires' => ['NonExistent' => '^1.0'],
        ]);
        Modular::refresh();

        $result = Modular::validate('Blog');

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->errors);
    }

    public function test_can_get_dependents(): void
    {
        $this->createTestModule('Core');
        $this->createTestModule('Blog', [
            'requires' => ['Core' => '^1.0'],
        ]);
        $this->createTestModule('Products', [
            'requires' => ['Core' => '^1.0'],
        ]);

        Modular::refresh();

        $dependents = Modular::getDependents('Core');

        $this->assertCount(2, $dependents);
        $this->assertTrue($dependents->hasModule('Blog'));
        $this->assertTrue($dependents->hasModule('Products'));
    }

    public function test_can_get_dependencies(): void
    {
        $this->createTestModule('Core');
        $this->createTestModule('Users');
        $this->createTestModule('Blog', [
            'requires' => ['Core' => '^1.0', 'Users' => '^1.0'],
        ]);

        Modular::refresh();

        $dependencies = Modular::getDependencies('Blog');

        $this->assertCount(2, $dependencies);
        $this->assertTrue($dependencies->hasModule('Core'));
        $this->assertTrue($dependencies->hasModule('Users'));
    }
}
