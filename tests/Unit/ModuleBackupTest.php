<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit;

use Esegments\ModularArchitecture\GitHub\ModuleBackup;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;

class ModuleBackupTest extends TestCase
{
    protected ModuleBackup $backup;

    protected string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = $this->getTestModulesPath().'/../backups';

        $this->backup = new ModuleBackup(
            files: new Filesystem,
            backupPath: $this->backupPath,
        );

        // Ensure backup directory is clean
        app('files')->deleteDirectory($this->backupPath);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->backupPath);

        parent::tearDown();
    }

    public function test_creates_backup_of_module(): void
    {
        $modulePath = $this->createTestModule('BackupTest');

        $backupFullPath = $this->backup->backup('BackupTest', $modulePath);

        $this->assertDirectoryExists($backupFullPath);
        $this->assertFileExists($backupFullPath.'/module.json');
    }

    public function test_finds_latest_backup(): void
    {
        $modulePath = $this->createTestModule('BackupTest');

        // Create multiple backups
        $backup1 = $this->backup->backup('BackupTest', $modulePath);
        usleep(100000); // Small delay to ensure different timestamps
        $backup2 = $this->backup->backup('BackupTest', $modulePath);

        $latest = $this->backup->findLatestBackup('BackupTest');

        $this->assertNotNull($latest);
        $this->assertEquals($backup2, $latest);
    }

    public function test_returns_null_for_nonexistent_backup(): void
    {
        $latest = $this->backup->findLatestBackup('NonExistent');

        $this->assertNull($latest);
    }

    public function test_lists_all_backups(): void
    {
        $modulePath = $this->createTestModule('BackupTest');

        $this->backup->backup('BackupTest', $modulePath);
        // Sleep 1 second to ensure different timestamps
        sleep(1);
        $this->backup->backup('BackupTest', $modulePath);

        $backups = $this->backup->listBackups('BackupTest');

        $this->assertCount(2, $backups);
    }

    public function test_prunes_old_backups(): void
    {
        $modulePath = $this->createTestModule('BackupTest');

        // Create 5 backups with 1 second gap between each
        for ($i = 0; $i < 5; $i++) {
            $this->backup->backup('BackupTest', $modulePath);
            if ($i < 4) {
                sleep(1);
            }
        }

        // Prune to keep only 2
        $deleted = $this->backup->pruneBackups('BackupTest', 2);

        $this->assertEquals(3, $deleted);
        $this->assertCount(2, $this->backup->listBackups('BackupTest'));
    }

    public function test_prune_does_nothing_when_under_limit(): void
    {
        $modulePath = $this->createTestModule('BackupTest');

        $this->backup->backup('BackupTest', $modulePath);

        $deleted = $this->backup->pruneBackups('BackupTest', 3);

        $this->assertEquals(0, $deleted);
    }

    public function test_deletes_specific_backup(): void
    {
        $modulePath = $this->createTestModule('BackupTest');

        $backupPath = $this->backup->backup('BackupTest', $modulePath);
        $this->assertDirectoryExists($backupPath);

        $result = $this->backup->deleteBackup($backupPath);

        $this->assertTrue($result);
        $this->assertDirectoryDoesNotExist($backupPath);
    }

    public function test_restores_module_from_backup(): void
    {
        $modulePath = $this->createTestModule('BackupTest');

        // Create backup
        $this->backup->backup('BackupTest', $modulePath);

        // Delete original module
        app('files')->deleteDirectory($modulePath);
        $this->assertDirectoryDoesNotExist($modulePath);

        // Restore from backup
        $this->backup->restore('BackupTest', $modulePath);

        $this->assertDirectoryExists($modulePath);
        $this->assertFileExists($modulePath.'/module.json');
    }
}
