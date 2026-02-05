<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit;

use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleCollection;
use Esegments\ModularArchitecture\Module\ModuleManifest;
use Esegments\ModularArchitecture\Resolver\DependencyResolver;
use Esegments\ModularArchitecture\Tests\TestCase;
use Esegments\ModularArchitecture\Validation\DependencyValidator;

class DependencyValidatorTest extends TestCase
{
    protected DependencyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new DependencyValidator(
            resolver: new DependencyResolver
        );
    }

    public function test_validates_satisfied_dependencies(): void
    {
        $collection = new ModuleCollection;
        $collection->put('Core', $this->createModule('Core', '1.0.0'));
        $collection->put('Blog', $this->createModule('Blog', '1.0.0', ['Core' => '^1.0']));

        $result = $this->validator->validate($collection);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->errors);
    }

    public function test_detects_missing_dependency(): void
    {
        $collection = new ModuleCollection;
        $collection->put('Blog', $this->createModule('Blog', '1.0.0', ['Core' => '^1.0']));

        $result = $this->validator->validate($collection);

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('Core', $result->errors[0]);
        $this->assertStringContainsString('not installed', $result->errors[0]);
    }

    public function test_detects_version_mismatch(): void
    {
        $collection = new ModuleCollection;
        $collection->put('Core', $this->createModule('Core', '0.5.0'));
        $collection->put('Blog', $this->createModule('Blog', '1.0.0', ['Core' => '^1.0']));

        $result = $this->validator->validate($collection);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('^1.0', $result->errors[0]);
        $this->assertStringContainsString('0.5.0', $result->errors[0]);
    }

    public function test_warns_about_disabled_dependency(): void
    {
        $collection = new ModuleCollection;
        $core = $this->createModule('Core', '1.0.0');
        $core->disable();
        $collection->put('Core', $core);
        $collection->put('Blog', $this->createModule('Blog', '1.0.0', ['Core' => '^1.0']));

        $result = $this->validator->validate($collection);

        $this->assertTrue($result->hasWarnings());
        $this->assertStringContainsString('disabled', $result->warnings[0]);
    }

    public function test_can_enable_module_with_satisfied_dependencies(): void
    {
        $collection = new ModuleCollection;
        $collection->put('Core', $this->createModule('Core', '1.0.0'));
        $blog = $this->createModule('Blog', '1.0.0', ['Core' => '^1.0']);
        $blog->disable();

        $result = $this->validator->canEnable($blog, $collection);

        $this->assertTrue($result->isValid());
    }

    public function test_cannot_enable_module_with_missing_dependencies(): void
    {
        $collection = new ModuleCollection;
        $blog = $this->createModule('Blog', '1.0.0', ['Core' => '^1.0']);
        $blog->disable();

        $result = $this->validator->canEnable($blog, $collection);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('not installed', $result->errors[0]);
    }

    public function test_cannot_disable_module_with_dependents(): void
    {
        $collection = new ModuleCollection;
        $collection->put('Core', $this->createModule('Core', '1.0.0'));
        $collection->put('Blog', $this->createModule('Blog', '1.0.0', ['Core' => '^1.0']));

        $core = $collection->findByName('Core');
        $result = $this->validator->canDisable($core, $collection);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('depend on this module', $result->errors[0]);
    }

    public function test_can_disable_module_without_dependents(): void
    {
        $collection = new ModuleCollection;
        $collection->put('Core', $this->createModule('Core', '1.0.0'));
        $collection->put('Blog', $this->createModule('Blog', '1.0.0'));

        $blog = $collection->findByName('Blog');
        $result = $this->validator->canDisable($blog, $collection);

        $this->assertTrue($result->isValid());
    }

    public function test_cannot_disable_protected_module(): void
    {
        $collection = new ModuleCollection;
        $core = $this->createModule('Core', '1.0.0');
        $core->setProtected(true);
        $collection->put('Core', $core);

        $result = $this->validator->canDisable($core, $collection);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('protected', $result->errors[0]);
    }

    public function test_validates_semver_constraints(): void
    {
        $this->assertTrue($this->validator->satisfiesConstraint('1.0.0', '^1.0'));
        $this->assertTrue($this->validator->satisfiesConstraint('1.5.0', '^1.0'));
        $this->assertFalse($this->validator->satisfiesConstraint('2.0.0', '^1.0'));

        // ~1.5 means >=1.5.0 <2.0.0 in Composer semver
        $this->assertTrue($this->validator->satisfiesConstraint('1.5.0', '~1.5'));
        $this->assertTrue($this->validator->satisfiesConstraint('1.5.9', '~1.5'));
        $this->assertTrue($this->validator->satisfiesConstraint('1.6.0', '~1.5'));
        $this->assertTrue($this->validator->satisfiesConstraint('1.9.9', '~1.5'));
        $this->assertFalse($this->validator->satisfiesConstraint('2.0.0', '~1.5'));

        // ~1.5.0 means >=1.5.0 <1.6.0
        $this->assertTrue($this->validator->satisfiesConstraint('1.5.0', '~1.5.0'));
        $this->assertTrue($this->validator->satisfiesConstraint('1.5.9', '~1.5.0'));
        $this->assertFalse($this->validator->satisfiesConstraint('1.6.0', '~1.5.0'));

        $this->assertTrue($this->validator->satisfiesConstraint('2.0.0', '>=2.0 <3.0'));
        $this->assertTrue($this->validator->satisfiesConstraint('2.9.9', '>=2.0 <3.0'));
        $this->assertFalse($this->validator->satisfiesConstraint('3.0.0', '>=2.0 <3.0'));

        $this->assertTrue($this->validator->satisfiesConstraint('1.0.0', '*'));
        $this->assertTrue($this->validator->satisfiesConstraint('99.99.99', '*'));
    }

    protected function createModule(string $name, string $version, array $requires = []): Module
    {
        $path = $this->getTestModulesPath().'/'.$name;
        $this->app['files']->ensureDirectoryExists($path);

        $manifest = new ModuleManifest(
            name: $name,
            version: $version,
            alias: strtolower($name),
            requires: $requires,
        );

        $this->app['files']->put(
            $path.'/module.json',
            $manifest->toJson()
        );

        return Module::fromPath($path);
    }
}
