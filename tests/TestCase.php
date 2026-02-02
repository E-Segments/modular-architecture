<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests;

use Esegments\ModularArchitecture\ModularServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create test modules directory
        $this->app['files']->ensureDirectoryExists($this->getTestModulesPath());
    }

    protected function tearDown(): void
    {
        // Clean up test modules
        $this->app['files']->deleteDirectory($this->getTestModulesPath());

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ModularServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Modular' => \Esegments\ModularArchitecture\Facades\Modular::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('modular.paths', [
            $this->getTestModulesPath(),
        ]);

        $app['config']->set('modular.cache.enabled', false);
        $app['config']->set('modular.state_file', $this->getTestModulesPath() . '/modules_statuses.json');
    }

    protected function getTestModulesPath(): string
    {
        return __DIR__ . '/Fixtures/Modules';
    }

    protected function createTestModule(string $name, array $manifest = []): string
    {
        $path = $this->getTestModulesPath() . '/' . $name;
        $this->app['files']->ensureDirectoryExists($path . '/app/Providers');

        // Create module.json
        $defaultManifest = [
            'name' => $name,
            'version' => '1.0.0',
            'alias' => strtolower($name),
            'description' => "Test {$name} module",
            'priority' => 0,
            'providers' => [],
            'requires' => [],
        ];

        $this->app['files']->put(
            $path . '/module.json',
            json_encode(array_merge($defaultManifest, $manifest), JSON_PRETTY_PRINT)
        );

        return $path;
    }
}
