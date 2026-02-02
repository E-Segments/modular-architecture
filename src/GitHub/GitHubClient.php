<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\GitHub;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GitHubClient
{
    protected const BASE_URL = 'https://api.github.com';

    public function __construct(
        protected readonly ?string $token = null,
        protected readonly int $timeout = 30,
        protected readonly bool $verifySsl = true,
    ) {}

    /**
     * Get repository information.
     */
    public function getRepository(string $owner, string $repo): ?array
    {
        $response = $this->request()
            ->get(self::BASE_URL . "/repos/{$owner}/{$repo}");

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Get repository releases.
     */
    public function getReleases(string $owner, string $repo, int $perPage = 10): array
    {
        $response = $this->request()
            ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/releases", [
                'per_page' => $perPage,
            ]);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Get latest release.
     */
    public function getLatestRelease(string $owner, string $repo): ?array
    {
        $response = $this->request()
            ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/releases/latest");

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Get a specific release by tag.
     */
    public function getReleaseByTag(string $owner, string $repo, string $tag): ?array
    {
        $response = $this->request()
            ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/releases/tags/{$tag}");

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Get repository tags.
     */
    public function getTags(string $owner, string $repo, int $perPage = 30): array
    {
        $response = $this->request()
            ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/tags", [
                'per_page' => $perPage,
            ]);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Get default branch.
     */
    public function getDefaultBranch(string $owner, string $repo): ?string
    {
        $repoInfo = $this->getRepository($owner, $repo);

        return $repoInfo['default_branch'] ?? null;
    }

    /**
     * Download repository archive (zip).
     */
    public function downloadArchive(string $owner, string $repo, string $ref = 'main'): ?Response
    {
        $response = $this->request()
            ->withOptions(['allow_redirects' => true])
            ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/zipball/{$ref}");

        return $response->successful() ? $response : null;
    }

    /**
     * Get archive download URL.
     */
    public function getArchiveUrl(string $owner, string $repo, string $ref = 'main'): string
    {
        return "https://github.com/{$owner}/{$repo}/archive/refs/heads/{$ref}.zip";
    }

    /**
     * Get release asset download URL.
     */
    public function getReleaseArchiveUrl(string $owner, string $repo, string $tag): string
    {
        return "https://github.com/{$owner}/{$repo}/archive/refs/tags/{$tag}.zip";
    }

    /**
     * Get file contents from repository.
     */
    public function getFileContents(string $owner, string $repo, string $path, string $ref = 'main'): ?string
    {
        $response = $this->request()
            ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/contents/{$path}", [
                'ref' => $ref,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (isset($data['content']) && $data['encoding'] === 'base64') {
            return base64_decode($data['content']);
        }

        return null;
    }

    /**
     * Check if repository has module.json.
     */
    public function hasModuleJson(string $owner, string $repo, string $ref = 'main'): bool
    {
        return $this->getFileContents($owner, $repo, 'module.json', $ref) !== null;
    }

    /**
     * Get module.json from repository.
     */
    public function getModuleJson(string $owner, string $repo, string $ref = 'main'): ?array
    {
        $contents = $this->getFileContents($owner, $repo, 'module.json', $ref);

        if (! $contents) {
            return null;
        }

        return json_decode($contents, true);
    }

    /**
     * Search repositories.
     */
    public function searchRepositories(string $query, int $perPage = 10): array
    {
        $response = $this->request()
            ->get(self::BASE_URL . '/search/repositories', [
                'q' => $query,
                'per_page' => $perPage,
            ]);

        return $response->successful() ? ($response->json()['items'] ?? []) : [];
    }

    /**
     * Get rate limit status.
     */
    public function getRateLimit(): array
    {
        $response = $this->request()
            ->get(self::BASE_URL . '/rate_limit');

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Create HTTP request with authentication.
     */
    protected function request(): PendingRequest
    {
        $request = Http::timeout($this->timeout)
            ->withOptions(['verify' => $this->verifySsl])
            ->withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Esegments-Modular/1.0',
            ]);

        if ($this->token) {
            $request->withToken($this->token);
        }

        return $request;
    }

    /**
     * Parse repository identifier (owner/repo).
     *
     * @return array{owner: string, repo: string}|null
     */
    public static function parseRepoIdentifier(string $identifier): ?array
    {
        // Handle full GitHub URLs
        if (str_contains($identifier, 'github.com')) {
            $pattern = '/github\.com[\/:]([^\/]+)\/([^\/\.]+)/';
            if (preg_match($pattern, $identifier, $matches)) {
                return ['owner' => $matches[1], 'repo' => rtrim($matches[2], '.git')];
            }
        }

        // Handle owner/repo format
        if (preg_match('/^([^\/]+)\/([^\/]+)$/', $identifier, $matches)) {
            return ['owner' => $matches[1], 'repo' => $matches[2]];
        }

        return null;
    }
}
