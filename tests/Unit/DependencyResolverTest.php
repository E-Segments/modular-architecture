<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit;

use Esegments\ModularArchitecture\Exceptions\CircularDependencyException;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleCollection;
use Esegments\ModularArchitecture\Module\ModuleManifest;
use Esegments\ModularArchitecture\Resolver\DependencyResolver;
use Esegments\ModularArchitecture\Tests\TestCase;

class DependencyResolverTest extends TestCase
{
    protected DependencyResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DependencyResolver;
    }

    public function test_resolves_modules_in_dependency_order(): void
    {
        $collection = new ModuleCollection;

        // C depends on B, B depends on A
        $collection->put('A', $this->createModule('A'));
        $collection->put('B', $this->createModule('B', ['A' => '^1.0']));
        $collection->put('C', $this->createModule('C', ['B' => '^1.0']));

        $resolved = $this->resolver->resolve($collection);

        $names = $resolved->names();

        // A must come before B, B must come before C
        $this->assertLessThan(
            array_search('B', $names),
            array_search('A', $names)
        );
        $this->assertLessThan(
            array_search('C', $names),
            array_search('B', $names)
        );
    }

    public function test_detects_circular_dependencies(): void
    {
        $collection = new ModuleCollection;

        // A -> B -> C -> A (cycle)
        $collection->put('A', $this->createModule('A', ['C' => '^1.0']));
        $collection->put('B', $this->createModule('B', ['A' => '^1.0']));
        $collection->put('C', $this->createModule('C', ['B' => '^1.0']));

        $cycles = $this->resolver->detectCycles($collection);

        $this->assertNotEmpty($cycles);
    }

    public function test_throws_on_circular_dependency_during_resolve(): void
    {
        $this->expectException(CircularDependencyException::class);

        $collection = new ModuleCollection;

        $collection->put('A', $this->createModule('A', ['B' => '^1.0']));
        $collection->put('B', $this->createModule('B', ['A' => '^1.0']));

        $this->resolver->resolve($collection);
    }

    public function test_handles_empty_collection(): void
    {
        $collection = new ModuleCollection;
        $resolved = $this->resolver->resolve($collection);

        $this->assertTrue($resolved->isEmpty());
    }

    public function test_handles_no_dependencies(): void
    {
        $collection = new ModuleCollection;

        $collection->put('A', $this->createModule('A'));
        $collection->put('B', $this->createModule('B'));
        $collection->put('C', $this->createModule('C'));

        $resolved = $this->resolver->resolve($collection);

        $this->assertCount(3, $resolved);
    }

    public function test_ignores_missing_dependencies(): void
    {
        $collection = new ModuleCollection;

        // B requires NonExistent which is not in the collection
        $collection->put('A', $this->createModule('A'));
        $collection->put('B', $this->createModule('B', ['NonExistent' => '^1.0']));

        $resolved = $this->resolver->resolve($collection);

        $this->assertCount(2, $resolved);
    }

    public function test_would_create_cycle_check(): void
    {
        $collection = new ModuleCollection;

        $collection->put('A', $this->createModule('A'));
        $collection->put('B', $this->createModule('B', ['A' => '^1.0']));

        // New module that would create a cycle: A -> C, but C -> B -> A
        $newModule = $this->createModule('C', ['B' => '^1.0']);
        $aModule = $this->createModule('A', ['C' => '^1.0']);

        $testCollection = new ModuleCollection($collection->all());
        $testCollection->put('A', $aModule);
        $testCollection->put('C', $newModule);

        $this->assertTrue($this->resolver->wouldCreateCycle($newModule, $testCollection));
    }

    protected function createModule(string $name, array $requires = []): Module
    {
        $path = $this->getTestModulesPath().'/'.$name;
        $this->app['files']->ensureDirectoryExists($path);

        $manifest = new ModuleManifest(
            name: $name,
            version: '1.0.0',
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
