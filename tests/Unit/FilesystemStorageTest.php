<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit;

use Esegments\ModularArchitecture\Storage\FilesystemStorage;
use Esegments\ModularArchitecture\Tests\TestCase;

class FilesystemStorageTest extends TestCase
{
    protected FilesystemStorage $storage;

    protected string $statesFile;

    protected string $metadataFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statesFile = $this->getTestModulesPath().'/states.json';
        $this->metadataFile = $this->getTestModulesPath().'/metadata.json';

        $this->storage = new FilesystemStorage(
            files: $this->app['files'],
            statesFile: $this->statesFile,
            metadataFile: $this->metadataFile,
        );
    }

    public function test_can_set_and_get_state(): void
    {
        $this->storage->setState('Blog', true);
        $this->assertTrue($this->storage->getState('Blog'));

        $this->storage->setState('Blog', false);
        $this->assertFalse($this->storage->getState('Blog'));
    }

    public function test_returns_null_for_unknown_module_state(): void
    {
        $this->assertNull($this->storage->getState('Unknown'));
    }

    public function test_can_get_all_states(): void
    {
        $this->storage->setState('Blog', true);
        $this->storage->setState('Products', false);
        $this->storage->setState('Core', true);

        $states = $this->storage->getAllStates();

        $this->assertCount(3, $states);
        $this->assertTrue($states['Blog']);
        $this->assertFalse($states['Products']);
        $this->assertTrue($states['Core']);
    }

    public function test_can_remove_state(): void
    {
        $this->storage->setState('Blog', true);
        $this->assertTrue($this->storage->getState('Blog'));

        $this->storage->removeState('Blog');
        $this->assertNull($this->storage->getState('Blog'));
    }

    public function test_can_set_and_get_metadata(): void
    {
        $metadata = [
            'source' => 'github:esegments/blog',
            'version' => '1.0.0',
            'installed_at' => '2024-01-01T00:00:00Z',
        ];

        $this->storage->setMetadata('Blog', $metadata);

        $retrieved = $this->storage->getMetadata('Blog');

        $this->assertEquals('github:esegments/blog', $retrieved['source']);
        $this->assertEquals('1.0.0', $retrieved['version']);
        $this->assertArrayHasKey('updated_at', $retrieved);
    }

    public function test_metadata_merges_on_update(): void
    {
        $this->storage->setMetadata('Blog', ['source' => 'github', 'version' => '1.0.0']);
        $this->storage->setMetadata('Blog', ['version' => '1.1.0']);

        $metadata = $this->storage->getMetadata('Blog');

        $this->assertEquals('github', $metadata['source']);
        $this->assertEquals('1.1.0', $metadata['version']);
    }

    public function test_returns_null_for_unknown_module_metadata(): void
    {
        $this->assertNull($this->storage->getMetadata('Unknown'));
    }

    public function test_can_remove_metadata(): void
    {
        $this->storage->setMetadata('Blog', ['version' => '1.0.0']);
        $this->assertNotNull($this->storage->getMetadata('Blog'));

        $this->storage->removeMetadata('Blog');
        $this->assertNull($this->storage->getMetadata('Blog'));
    }

    public function test_can_clear_all_storage(): void
    {
        $this->storage->setState('Blog', true);
        $this->storage->setMetadata('Blog', ['version' => '1.0.0']);

        $this->storage->clear();

        $this->assertEmpty($this->storage->getAllStates());
        $this->assertEmpty($this->storage->getAllMetadata());
    }

    public function test_is_available_creates_directory(): void
    {
        $this->assertTrue($this->storage->isAvailable());
        $this->assertDirectoryExists(dirname($this->statesFile));
    }

    public function test_persists_state_to_file(): void
    {
        $this->storage->setState('Blog', true);

        // Create new instance to verify persistence
        $newStorage = new FilesystemStorage(
            files: $this->app['files'],
            statesFile: $this->statesFile,
            metadataFile: $this->metadataFile,
        );

        $this->assertTrue($newStorage->getState('Blog'));
    }

    public function test_refresh_reloads_from_disk(): void
    {
        $this->storage->setState('Blog', true);

        // Modify file directly
        file_put_contents($this->statesFile, json_encode(['Blog' => false]));

        // Still cached
        $this->assertTrue($this->storage->getState('Blog'));

        // After refresh
        $this->storage->refresh();
        $this->assertFalse($this->storage->getState('Blog'));
    }
}
