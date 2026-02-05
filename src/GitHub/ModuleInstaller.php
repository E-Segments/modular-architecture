<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\GitHub;

use Esegments\ModularArchitecture\Contracts\GitHubClientContract;
use Esegments\ModularArchitecture\Discovery\PathScanner;
use Esegments\ModularArchitecture\Exceptions\ModuleNotFoundException;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Support\Json;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * Orchestrates module installation from GitHub.
 */
class ModuleInstaller
{
    public function __construct(
        protected readonly GitHubClientContract $github,
        protected readonly Filesystem $files,
        protected readonly PathScanner $pathScanner,
        protected readonly ArchiveHandler $archiveHandler,
        protected readonly ModuleBackup $backup,
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
        $installPath = $this->pathScanner->getDefaultPath().'/'.$moduleName;

        // Check if module already exists
        if ($this->files->isDirectory($installPath)) {
            throw new RuntimeException("Module [{$moduleName}] already exists at {$installPath}");
        }

        // Download and extract
        $archivePath = $this->archiveHandler->download($owner, $repo, $ref);

        try {
            $this->archiveHandler->extract($archivePath, $installPath);
        } finally {
            $this->archiveHandler->cleanup($archivePath);
        }

        // Load the installed module
        $module = Module::fromPath($installPath);

        // Save metadata
        $this->saveMetadata($installPath, [
            'source' => "{$owner}/{$repo}",
            'version' => $ref,
            'installed_at' => now()->toIso8601String(),
        ]);

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

        $metadata = $this->getInstalledMetadata($moduleName);

        if (! isset($metadata['source'])) {
            throw new RuntimeException("Module [{$moduleName}] was not installed from a remote source");
        }

        $parsed = GitHubClient::parseRepoIdentifier($metadata['source']);
        if (! $parsed) {
            throw new RuntimeException("Invalid source for module [{$moduleName}]: {$metadata['source']}");
        }

        // Backup current module
        $this->backup->backup($moduleName, $modulePath);

        try {
            // Remove current module
            $this->files->deleteDirectory($modulePath);

            // Install new version
            return $this->install($metadata['source'], $version);
        } catch (\Exception $e) {
            // Restore from backup on failure
            $this->backup->restore($moduleName, $modulePath);
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
     * Get installed module metadata.
     */
    protected function getInstalledMetadata(string $moduleName): array
    {
        $modulePath = $this->pathScanner->findModule($moduleName);
        if (! $modulePath) {
            return [];
        }

        $metadataFile = $modulePath.'/.modular-metadata.json';

        if (! $this->files->exists($metadataFile)) {
            return [];
        }

        return Json::decode($this->files->get($metadataFile)) ?? [];
    }

    /**
     * Save installed module metadata.
     */
    public function saveMetadata(string $modulePath, array $metadata): void
    {
        $metadataFile = rtrim($modulePath, '/').'/.modular-metadata.json';

        $this->files->put(
            $metadataFile,
            Json::encode($metadata)
        );
    }
}
