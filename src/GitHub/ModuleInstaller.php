<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\GitHub;

use Esegments\ModularArchitecture\Discovery\PathScanner;
use Esegments\ModularArchitecture\Exceptions\ModuleNotFoundException;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleManifest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use ZipArchive;

class ModuleInstaller
{
    public function __construct(
        protected readonly GitHubClient $github,
        protected readonly Filesystem $files,
        protected readonly PathScanner $pathScanner,
        protected readonly string $tempPath,
        protected readonly string $backupPath,
    ) {}

    /**
     * Install a module from GitHub.
     */
    public function install(string $identifier, ?string $version = null): InstallResult
    {
        $parsed = GitHubClient::parseRepoIdentifier($identifier);

        if (! $parsed) {
            throw new RuntimeException("Invalid repository identifier: {$identifier}");
        }

        $owner = $parsed['owner'];
        $repo = $parsed['repo'];

        // Get repository info
        $repoInfo = $this->github->getRepository($owner, $repo);
        if (! $repoInfo) {
            throw new ModuleNotFoundException($repo, "Repository not found: {$owner}/{$repo}");
        }

        // Determine version/ref to install
        $ref = $this->resolveVersion($owner, $repo, $version);

        // Check if module.json exists
        $moduleJson = $this->github->getModuleJson($owner, $repo, $ref);
        if (! $moduleJson) {
            throw new RuntimeException("Repository does not contain a valid module.json: {$owner}/{$repo}");
        }

        $moduleName = $moduleJson['name'] ?? $repo;

        // Download archive
        $archivePath = $this->download($owner, $repo, $ref);

        // Extract and install
        $installPath = $this->extract($archivePath, $moduleName);

        // Clean up download
        $this->files->delete($archivePath);

        // Load the installed module
        $module = Module::fromPath($installPath);

        return new InstallResult(
            success: true,
            module: $module,
            source: "{$owner}/{$repo}",
            version: $ref,
            path: $installPath,
        );
    }

    /**
     * Update an installed module.
     */
    public function update(string $moduleName, ?string $version = null): InstallResult
    {
        $modulePath = $this->pathScanner->findModule($moduleName);

        if (! $modulePath) {
            throw new ModuleNotFoundException($moduleName);
        }

        // Get current module info
        $currentModule = Module::fromPath($modulePath);
        $metadata = $this->getInstalledMetadata($moduleName);

        if (! isset($metadata['source'])) {
            throw new RuntimeException("Module [{$moduleName}] was not installed from a remote source");
        }

        $parsed = GitHubClient::parseRepoIdentifier($metadata['source']);
        if (! $parsed) {
            throw new RuntimeException("Invalid source for module [{$moduleName}]: {$metadata['source']}");
        }

        // Backup current module
        $this->backup($moduleName, $modulePath);

        try {
            // Remove current module
            $this->files->deleteDirectory($modulePath);

            // Install new version
            return $this->install($metadata['source'], $version);
        } catch (\Exception $e) {
            // Restore from backup on failure
            $this->restore($moduleName, $modulePath);
            throw $e;
        }
    }

    /**
     * Check for available updates.
     */
    public function checkForUpdates(string $moduleName): ?array
    {
        $metadata = $this->getInstalledMetadata($moduleName);

        if (! isset($metadata['source'])) {
            return null;
        }

        $parsed = GitHubClient::parseRepoIdentifier($metadata['source']);
        if (! $parsed) {
            return null;
        }

        $latest = $this->github->getLatestRelease($parsed['owner'], $parsed['repo']);

        if (! $latest) {
            // No releases, check tags
            $tags = $this->github->getTags($parsed['owner'], $parsed['repo'], 1);
            if (empty($tags)) {
                return null;
            }
            $latestVersion = $tags[0]['name'];
        } else {
            $latestVersion = $latest['tag_name'];
        }

        $currentVersion = $metadata['version'] ?? '0.0.0';

        // Remove 'v' prefix if present
        $latestVersion = ltrim($latestVersion, 'v');
        $currentVersion = ltrim($currentVersion, 'v');

        if (version_compare($latestVersion, $currentVersion, '>')) {
            return [
                'current' => $currentVersion,
                'latest' => $latestVersion,
                'source' => $metadata['source'],
            ];
        }

        return null;
    }

    /**
     * Resolve version to a git ref.
     */
    protected function resolveVersion(string $owner, string $repo, ?string $version): string
    {
        if ($version) {
            // Check if it's a valid tag
            $release = $this->github->getReleaseByTag($owner, $repo, $version);
            if ($release) {
                return $version;
            }

            // Check if it's a tag without 'v' prefix
            $release = $this->github->getReleaseByTag($owner, $repo, "v{$version}");
            if ($release) {
                return "v{$version}";
            }

            // Assume it's a branch name
            return $version;
        }

        // Try to get latest release
        $latest = $this->github->getLatestRelease($owner, $repo);
        if ($latest) {
            return $latest['tag_name'];
        }

        // Fall back to default branch
        return $this->github->getDefaultBranch($owner, $repo) ?? 'main';
    }

    /**
     * Download repository archive.
     */
    protected function download(string $owner, string $repo, string $ref): string
    {
        $this->files->ensureDirectoryExists($this->tempPath);

        $filename = "{$owner}-{$repo}-" . md5($ref) . '.zip';
        $filepath = "{$this->tempPath}/{$filename}";

        // Get download URL
        if (str_starts_with($ref, 'v') || preg_match('/^\d+\.\d+/', $ref)) {
            $url = $this->github->getReleaseArchiveUrl($owner, $repo, $ref);
        } else {
            $url = $this->github->getArchiveUrl($owner, $repo, $ref);
        }

        $response = Http::timeout(120)
            ->withOptions(['sink' => $filepath])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Failed to download module archive: HTTP {$response->status()}");
        }

        return $filepath;
    }

    /**
     * Extract archive and install module.
     */
    protected function extract(string $archivePath, string $moduleName): string
    {
        $zip = new ZipArchive();
        $result = $zip->open($archivePath);

        if ($result !== true) {
            throw new RuntimeException("Failed to open archive: error code {$result}");
        }

        // Extract to temp directory first
        $extractPath = "{$this->tempPath}/extract-" . uniqid();
        $this->files->ensureDirectoryExists($extractPath);

        $zip->extractTo($extractPath);
        $zip->close();

        // Find the extracted folder (GitHub archives have a root folder)
        $extracted = $this->files->directories($extractPath);
        if (empty($extracted)) {
            throw new RuntimeException('Archive is empty or invalid');
        }

        $sourcePath = $extracted[0];

        // Determine install path
        $installPath = $this->pathScanner->getDefaultPath() . '/' . $moduleName;

        // Check if module already exists
        if ($this->files->isDirectory($installPath)) {
            throw new RuntimeException("Module [{$moduleName}] already exists at {$installPath}");
        }

        // Move to modules directory
        $this->files->moveDirectory($sourcePath, $installPath);

        // Clean up extract directory
        $this->files->deleteDirectory($extractPath);

        return $installPath;
    }

    /**
     * Backup a module before update.
     */
    protected function backup(string $moduleName, string $modulePath): string
    {
        $this->files->ensureDirectoryExists($this->backupPath);

        $backupName = "{$moduleName}-" . date('Y-m-d-His');
        $backupFullPath = "{$this->backupPath}/{$backupName}";

        $this->files->copyDirectory($modulePath, $backupFullPath);

        return $backupFullPath;
    }

    /**
     * Restore a module from backup.
     */
    protected function restore(string $moduleName, string $targetPath): void
    {
        // Find most recent backup
        $backups = $this->files->directories($this->backupPath);
        $moduleBackups = array_filter($backups, fn ($path) => str_starts_with(basename($path), $moduleName));

        if (empty($moduleBackups)) {
            throw new RuntimeException("No backup found for module [{$moduleName}]");
        }

        // Sort by name (which includes timestamp)
        rsort($moduleBackups);
        $latestBackup = $moduleBackups[0];

        // Restore
        $this->files->copyDirectory($latestBackup, $targetPath);
    }

    /**
     * Get installed module metadata.
     */
    protected function getInstalledMetadata(string $moduleName): array
    {
        $metadataFile = $this->pathScanner->findModule($moduleName) . '/.modular-metadata.json';

        if (! $this->files->exists($metadataFile)) {
            return [];
        }

        return json_decode($this->files->get($metadataFile), true) ?? [];
    }

    /**
     * Save installed module metadata.
     */
    public function saveMetadata(string $modulePath, array $metadata): void
    {
        $metadataFile = rtrim($modulePath, '/') . '/.modular-metadata.json';

        $this->files->put(
            $metadataFile,
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
