<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Scaffolding;

use Illuminate\Filesystem\Filesystem;

class StubProcessor
{
    public function __construct(
        protected readonly Filesystem $files,
        protected readonly string $stubPath,
    ) {}

    /**
     * Process a stub file with replacements.
     *
     * @param  array<string, string>  $replacements
     */
    public function process(string $stubName, array $replacements): string
    {
        $stubFile = $this->getStubPath($stubName);

        if (! $this->files->exists($stubFile)) {
            throw new \RuntimeException("Stub file not found: {$stubFile}");
        }

        $content = $this->files->get($stubFile);

        foreach ($replacements as $key => $value) {
            $content = str_replace("{{ {$key} }}", $value, $content);
            $content = str_replace("{{{$key}}}", $value, $content);
        }

        return $content;
    }

    /**
     * Process and write a stub to a destination.
     *
     * @param  array<string, string>  $replacements
     */
    public function processAndWrite(string $stubName, string $destination, array $replacements): void
    {
        $content = $this->process($stubName, $replacements);

        $this->files->ensureDirectoryExists(dirname($destination));
        $this->files->put($destination, $content);
    }

    /**
     * Get the full path to a stub file.
     */
    public function getStubPath(string $stubName): string
    {
        return rtrim($this->stubPath, '/').'/'.ltrim($stubName, '/');
    }

    /**
     * Check if a stub exists.
     */
    public function stubExists(string $stubName): bool
    {
        return $this->files->exists($this->getStubPath($stubName));
    }

    /**
     * Get all available stubs.
     *
     * @return array<string>
     */
    public function getAvailableStubs(): array
    {
        if (! $this->files->isDirectory($this->stubPath)) {
            return [];
        }

        return collect($this->files->allFiles($this->stubPath))
            ->map(fn ($file) => $file->getRelativePathname())
            ->all();
    }

    /**
     * Build standard replacements for a module.
     *
     * @return array<string, string>
     */
    public static function buildReplacements(
        string $moduleName,
        string $version = '1.0.0',
        string $description = '',
        ?string $vendor = null,
        ?string $author = null,
        ?string $email = null,
    ): array {
        $studlyName = str($moduleName)->studly()->toString();
        $snakeName = str($moduleName)->snake()->toString();
        $kebabName = str($moduleName)->kebab()->toString();
        $lowerName = str($moduleName)->lower()->toString();

        return [
            'MODULE_NAME' => $studlyName,
            'MODULE_STUDLY' => $studlyName,
            'MODULE_SNAKE' => $snakeName,
            'MODULE_KEBAB' => $kebabName,
            'MODULE_LOWER' => $lowerName,
            'MODULE_NAMESPACE' => "Modules\\{$studlyName}",
            'MODULE_VERSION' => $version,
            'MODULE_DESCRIPTION' => $description,
            'VENDOR' => $vendor ?? 'esegments',
            'AUTHOR_NAME' => $author ?? 'Your Name',
            'AUTHOR_EMAIL' => $email ?? 'your@email.com',
            'YEAR' => (string) date('Y'),
        ];
    }
}
