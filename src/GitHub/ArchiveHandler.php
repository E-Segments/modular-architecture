<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\GitHub;

use Esegments\ModularArchitecture\Contracts\GitHubClientContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use ZipArchive;

/**
 * Handles downloading and extracting module archives.
 */
class ArchiveHandler
{
    public function __construct(
        protected readonly GitHubClientContract $github,
        protected readonly Filesystem $files,
        protected readonly string $tempPath,
    ) {}

    /**
     * Download repository archive.
     */
    public function download(string $owner, string $repo, string $ref): string
    {
        $this->files->ensureDirectoryExists($this->tempPath);

        $filename = "{$owner}-{$repo}-".md5($ref).'.zip';
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
     * Extract archive to target path.
     */
    public function extract(string $archivePath, string $targetPath): string
    {
        $zip = new ZipArchive;
        $result = $zip->open($archivePath);

        if ($result !== true) {
            throw new RuntimeException("Failed to open archive: error code {$result}");
        }

        // Extract to temp directory first
        $extractPath = "{$this->tempPath}/extract-".uniqid();
        $this->files->ensureDirectoryExists($extractPath);

        $zip->extractTo($extractPath);
        $zip->close();

        // Find the extracted folder (GitHub archives have a root folder)
        $extracted = $this->files->directories($extractPath);
        if (empty($extracted)) {
            throw new RuntimeException('Archive is empty or invalid');
        }

        $sourcePath = $extracted[0];

        // Check if target already exists
        if ($this->files->isDirectory($targetPath)) {
            $this->files->deleteDirectory($extractPath);
            throw new RuntimeException("Target path already exists: {$targetPath}");
        }

        // Move to target path
        $this->files->moveDirectory($sourcePath, $targetPath);

        // Clean up extract directory
        $this->files->deleteDirectory($extractPath);

        return $targetPath;
    }

    /**
     * Clean up a downloaded archive.
     */
    public function cleanup(string $archivePath): void
    {
        if ($this->files->exists($archivePath)) {
            $this->files->delete($archivePath);
        }
    }
}
