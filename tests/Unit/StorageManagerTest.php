<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit;

use Esegments\ModularArchitecture\Contracts\ModuleStorageContract;
use Esegments\ModularArchitecture\Storage\StorageManager;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

class StorageManagerTest extends TestCase
{
    protected StorageManager $manager;

    protected string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storagePath = $this->getTestModulesPath().'/../storage';

        $this->manager = new StorageManager(
            files: new Filesystem,
            config: [
                'driver' => 'file',
                'path' => $this->storagePath,
                'states_file' => $this->storagePath.'/states.json',
                'metadata_file' => $this->storagePath.'/metadata.json',
            ],
        );

        app('files')->deleteDirectory($this->storagePath);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->storagePath);

        parent::tearDown();
    }

    public function test_returns_default_driver(): void
    {
        $driver = $this->manager->driver();

        $this->assertInstanceOf(ModuleStorageContract::class, $driver);
    }

    public function test_returns_same_driver_instance(): void
    {
        $driver1 = $this->manager->driver();
        $driver2 = $this->manager->driver();

        $this->assertSame($driver1, $driver2);
    }

    public function test_can_extend_with_custom_driver(): void
    {
        $customDriver = new class implements ModuleStorageContract
        {
            public function getState(string $name): ?bool
            {
                return null;
            }

            public function setState(string $name, bool $enabled): void {}

            public function getAllStates(): array
            {
                return ['custom' => true];
            }

            public function removeState(string $name): void {}

            public function getMetadata(string $name): ?array
            {
                return null;
            }

            public function setMetadata(string $name, array $metadata): void {}

            public function getAllMetadata(): array
            {
                return [];
            }

            public function removeMetadata(string $name): void {}

            public function clear(): void {}

            public function isAvailable(): bool
            {
                return true;
            }
        };

        $this->manager->extend('custom', fn () => $customDriver);

        $manager = new StorageManager(
            files: new Filesystem,
            config: [
                'driver' => 'custom',
            ],
        );
        $manager->extend('custom', fn () => $customDriver);

        $driver = $manager->driver();

        $this->assertSame($customDriver, $driver);
        $this->assertEquals(['custom' => true], $driver->getAllStates());
    }

    public function test_throws_exception_for_unknown_driver(): void
    {
        $manager = new StorageManager(
            files: new Filesystem,
            config: [
                'driver' => 'unknown',
            ],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver [unknown] is not supported');

        $manager->driver();
    }

    public function test_get_default_driver_name(): void
    {
        $this->assertEquals('file', $this->manager->getDefaultDriver());
    }

    public function test_creates_file_storage_driver(): void
    {
        $driver = $this->manager->driver('file');

        $this->assertInstanceOf(
            \Esegments\ModularArchitecture\Storage\FilesystemStorage::class,
            $driver
        );
    }
}
