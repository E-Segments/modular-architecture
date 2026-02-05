<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Contracts;

use Illuminate\Http\Client\Response;

/**
 * Contract for GitHub API client operations.
 */
interface GitHubClientContract
{
    /**
     * Get repository information.
     */
    public function getRepository(string $owner, string $repo): ?array;

    /**
     * Get repository releases.
     */
    public function getReleases(string $owner, string $repo, int $perPage = 10): array;

    /**
     * Get latest release.
     */
    public function getLatestRelease(string $owner, string $repo): ?array;

    /**
     * Get a specific release by tag.
     */
    public function getReleaseByTag(string $owner, string $repo, string $tag): ?array;

    /**
     * Get repository tags.
     */
    public function getTags(string $owner, string $repo, int $perPage = 30): array;

    /**
     * Get default branch.
     */
    public function getDefaultBranch(string $owner, string $repo): ?string;

    /**
     * Download repository archive (zip).
     */
    public function downloadArchive(string $owner, string $repo, string $ref = 'main'): ?Response;

    /**
     * Get archive download URL.
     */
    public function getArchiveUrl(string $owner, string $repo, string $ref = 'main'): string;

    /**
     * Get release asset download URL.
     */
    public function getReleaseArchiveUrl(string $owner, string $repo, string $tag): string;

    /**
     * Get file contents from repository.
     */
    public function getFileContents(string $owner, string $repo, string $path, string $ref = 'main'): ?string;

    /**
     * Check if repository has module.json.
     */
    public function hasModuleJson(string $owner, string $repo, string $ref = 'main'): bool;

    /**
     * Get module.json from repository.
     */
    public function getModuleJson(string $owner, string $repo, string $ref = 'main'): ?array;

    /**
     * Search repositories.
     */
    public function searchRepositories(string $query, int $perPage = 10): array;

    /**
     * Get rate limit status.
     */
    public function getRateLimit(): array;
}
