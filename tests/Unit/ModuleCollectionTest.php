<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit;

use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleCollection;
use Esegments\ModularArchitecture\Module\ModuleManifest;
use Esegments\ModularArchitecture\Tests\TestCase;

class ModuleCollectionTest extends TestCase
{
    public function test_filters_enabled_modules(): void
    {
        $collection = new ModuleCollection;
        $collection->put('A', $this->createModule('A'));
        $collection->put('B', $this->createModule('B'));

        $collection->findByName('B')->disable();

        $enabled = $collection->enabled();

        $this->assertCount(1, $enabled);
        $this->assertTrue($enabled->hasModule('A'));
        $this->assertFalse($enabled->hasModule('B'));
    }

    public function test_filters_disabled_modules(): void
    {
        $collection = new ModuleCollection;
        $collection->put('A', $this->createModule('A'));
        $collection->put('B', $this->createModule('B'));

        $collection->findByName('B')->disable();

        $disabled = $collection->disabled();

        $this->assertCount(1, $disabled);
        $this->assertTrue($disabled->hasModule('B'));
    }

    public function test_filters_protected_modules(): void
    {
        $collection = new ModuleCollection;
        $a = $this->createModule('A');
        $a->setProtected(true);
        $collection->put('A', $a);
        $collection->put('B', $this->createModule('B'));

        $protected = $collection->protected();

        $this->assertCount(1, $protected);
        $this->assertTrue($protected->hasModule('A'));
    }

    public function test_sorts_by_priority(): void
    {
        $collection = new ModuleCollection;
        $collection->put('A', $this->createModule('A', priority: 10));
        $collection->put('B', $this->createModule('B', priority: 0));
        $collection->put('C', $this->createModule('C', priority: 5));

        $sorted = $collection->sortByPriority();
        $names = $sorted->names();

        $this->assertEquals(['B', 'C', 'A'], $names);
    }

    public function test_sorts_by_priority_descending(): void
    {
        $collection = new ModuleCollection;
        $collection->put('A', $this->createModule('A', priority: 10));
        $collection->put('B', $this->createModule('B', priority: 0));
        $collection->put('C', $this->createModule('C', priority: 5));

        $sorted = $collection->sortByPriorityDesc();
        $names = $sorted->names();

        $this->assertEquals(['A', 'C', 'B'], $names);
    }

    public function test_finds_by_name(): void
    {
        $collection = new ModuleCollection;
        $collection->put('Blog', $this->createModule('Blog'));

        $found = $collection->findByName('Blog');
        $notFound = $collection->findByName('NonExistent');

        $this->assertNotNull($found);
        $this->assertEquals('Blog', $found->getName());
        $this->assertNull($notFound);
    }

    public function test_finds_by_alias(): void
    {
        $collection = new ModuleCollection;
        $collection->put('UserManagement', $this->createModule('UserManagement', alias: 'users'));

        $found = $collection->findByAlias('users');

        $this->assertNotNull($found);
        $this->assertEquals('UserManagement', $found->getName());
    }

    public function test_finds_modules_depending_on(): void
    {
        $collection = new ModuleCollection;
        $collection->put('Core', $this->createModule('Core'));
        $collection->put('Blog', $this->createModule('Blog', requires: ['Core' => '^1.0']));
        $collection->put('Products', $this->createModule('Products', requires: ['Core' => '^1.0']));
        $collection->put('Standalone', $this->createModule('Standalone'));

        $dependingOnCore = $collection->dependingOn('Core');

        $this->assertCount(2, $dependingOnCore);
        $this->assertTrue($dependingOnCore->hasModule('Blog'));
        $this->assertTrue($dependingOnCore->hasModule('Products'));
        $this->assertFalse($dependingOnCore->hasModule('Standalone'));
    }

    public function test_finds_dependencies_of(): void
    {
        $collection = new ModuleCollection;
        $collection->put('Core', $this->createModule('Core'));
        $collection->put('Users', $this->createModule('Users'));
        $collection->put('Blog', $this->createModule('Blog', requires: ['Core' => '^1.0', 'Users' => '^1.0']));

        $deps = $collection->dependenciesOf('Blog');

        $this->assertCount(2, $deps);
        $this->assertTrue($deps->hasModule('Core'));
        $this->assertTrue($deps->hasModule('Users'));
    }

    public function test_gets_all_providers(): void
    {
        $collection = new ModuleCollection;
        $collection->put('A', $this->createModule('A', providers: ['AProvider']));
        $collection->put('B', $this->createModule('B', providers: ['BProvider1', 'BProvider2']));

        $providers = $collection->allProviders();

        $this->assertCount(3, $providers);
        $this->assertContains('AProvider', $providers);
        $this->assertContains('BProvider1', $providers);
        $this->assertContains('BProvider2', $providers);
    }

    public function test_all_providers_excludes_disabled_modules(): void
    {
        $collection = new ModuleCollection;
        $collection->put('A', $this->createModule('A', providers: ['AProvider']));
        $b = $this->createModule('B', providers: ['BProvider']);
        $b->disable();
        $collection->put('B', $b);

        $providers = $collection->allProviders();

        $this->assertCount(1, $providers);
        $this->assertContains('AProvider', $providers);
        $this->assertNotContains('BProvider', $providers);
    }

    public function test_gets_names_as_array(): void
    {
        $collection = new ModuleCollection;
        $collection->put('Blog', $this->createModule('Blog'));
        $collection->put('Products', $this->createModule('Products'));

        $names = $collection->names();

        $this->assertEquals(['Blog', 'Products'], $names);
    }

    public function test_gets_aliases_as_array(): void
    {
        $collection = new ModuleCollection;
        $collection->put('Blog', $this->createModule('Blog', alias: 'blog'));
        $collection->put('Products', $this->createModule('Products', alias: 'products'));

        $aliases = $collection->aliases();

        $this->assertEquals(['blog', 'products'], $aliases);
    }

    public function test_checks_if_module_is_enabled(): void
    {
        $collection = new ModuleCollection;
        $collection->put('A', $this->createModule('A'));
        $b = $this->createModule('B');
        $b->disable();
        $collection->put('B', $b);

        $this->assertTrue($collection->isEnabled('A'));
        $this->assertFalse($collection->isEnabled('B'));
        $this->assertFalse($collection->isEnabled('NonExistent'));
    }

    protected function createModule(
        string $name,
        int $priority = 0,
        string $alias = '',
        array $requires = [],
        array $providers = [],
    ): Module {
        $path = $this->getTestModulesPath().'/'.$name;
        $this->app['files']->ensureDirectoryExists($path);

        $manifest = new ModuleManifest(
            name: $name,
            version: '1.0.0',
            alias: $alias ?: strtolower($name),
            priority: $priority,
            providers: $providers,
            requires: $requires,
        );

        $this->app['files']->put(
            $path.'/module.json',
            $manifest->toJson()
        );

        return Module::fromPath($path);
    }
}
