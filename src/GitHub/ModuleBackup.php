<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\GitHub;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * Handles module backup and restore operations.
 */
class ModuleBackup
{
    public function __construct(
        protected readonly Filesystem $files,
        protected readonly string $backupPath,
    ) {}

    /**
     * Backup a module before update.
     */
    public function backup(string $moduleName, string $modulePath): string
    {
        $this->files->ensureDirectoryExists($this->backupPath);

        $backupName = "{$moduleName}-".date('Y-m-d-His');
        $backupFullPath = "{$this->backupPath}/{$backupName}";

        $this->files->copyDirectory($modulePath, $backupFullPath);

        return $backupFullPath;
    }

    /**
     * Restore a module from backup.
     */
    public function restore(string $moduleName, string $targetPath): void
    {
        $latestBackup = $this->findLatestBackup($moduleName);

        if (! $latestBackup) {
            throw new RuntimeException("No backup found for module [{$moduleName}]");
        }

        $this->files->copyDirectory($latestBackup, $targetPath);
    }

    /**
     * Find the most recent backup for a module.
     */
    public function findLatestBackup(string $moduleName): ?string
    {
        if (! $this->files->isDirectory($this->backupPath)) {
            return null;
        }

        $backups = $this->files->directories($this->backupPath);
        $moduleBackups = array_filter(
            $backups,
            fn ($path) => str_starts_with(basename($path), $moduleName.'-')
        );

        if (empty($moduleBackups)) {
            return null;
        }

        // Sort by name (which includes timestamp) - newest first
        rsort($moduleBackups);

        return $moduleBackups[0];
    }

    /**
     * List all backups for a module.
     *
     * @return array<string>
     */
    public function listBackups(string $moduleName): array
    {
        if (! $this->files->isDirectory($this->backupPath)) {
            return [];
        }

        $backups = $this->files->directories($this->backupPath);

        return array_values(array_filter(
            $backups,
            fn ($path) => str_starts_with(basename($path), $moduleName.'-')
        ));
    }

    /**
     * Delete old backups, keeping only the most recent ones.
     */
    public function pruneBackups(string $moduleName, int $keep = 3): int
    {
        $backups = $this->listBackups($moduleName);

        if (count($backups) <= $keep) {
            return 0;
        }

        // Sort newest first
        rsort($backups);

        // Remove old backups
        $toDelete = array_slice($backups, $keep);
        $deleted = 0;

        foreach ($toDelete as $backup) {
            $this->files->deleteDirectory($backup);
            $deleted++;
        }

        return $deleted;
    }

    /**
     * Delete a specific backup.
     */
    public function deleteBackup(string $backupPath): bool
    {
        if (! $this->files->isDirectory($backupPath)) {
            return false;
        }

        return $this->files->deleteDirectory($backupPath);
    }
}
