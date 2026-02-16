<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands\Concerns;

use Esegments\ModularArchitecture\Modular;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

trait GeneratesModuleFiles
{
    protected ?Filesystem $files = null;

    /**
     * Get the filesystem instance.
     */
    protected function getFilesystem(): Filesystem
    {
        if ($this->files === null) {
            $this->files = new Filesystem;
        }

        return $this->files;
    }

    /**
     * Get the module instance.
     */
    protected function getModule(Modular $modular, string $moduleName): ?Module
    {
        if (! $modular->exists($moduleName)) {
            $this->components->error("Module [{$moduleName}] does not exist.");

            return null;
        }

        return $modular->find($moduleName);
    }

    /**
     * Get the stub file path.
     */
    protected function getStubPath(string $stub): string
    {
        $customPath = $this->laravel->basePath("stubs/modular/{$stub}.stub");

        if (file_exists($customPath)) {
            return $customPath;
        }

        return dirname(__DIR__, 3)."/stubs/{$stub}.stub";
    }

    /**
     * Get the stub contents with replacements.
     */
    protected function getStubContents(string $stub, array $replacements): string
    {
        $contents = file_get_contents($this->getStubPath($stub));

        foreach ($replacements as $search => $replace) {
            $contents = str_replace("{{ {$search} }}", $replace, $contents);
        }

        return $contents;
    }

    /**
     * Get the default replacements for a module.
     */
    protected function getDefaultReplacements(Module $module, string $name): array
    {
        return [
            'MODULE_NAME' => $module->getName(),
            'MODULE_LOWER' => Str::lower($module->getName()),
            'MODULE_NAMESPACE' => $module->getNamespace(),
            'CLASS_NAME' => $name,
            'CLASS_LOWER' => Str::lower($name),
            'CLASS_SNAKE' => Str::snake($name),
            'CLASS_KEBAB' => Str::kebab($name),
            'CLASS_PLURAL' => Str::plural($name),
            'CLASS_PLURAL_LOWER' => Str::lower(Str::plural($name)),
        ];
    }

    /**
     * Ensure the directory exists.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        $files = $this->getFilesystem();
        $directory = dirname($path);

        if (! $files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Write the file to disk.
     */
    protected function writeFile(string $path, string $contents): bool
    {
        $this->ensureDirectoryExists($path);

        if (file_exists($path) && ! $this->option('force')) {
            $this->components->error("File already exists: {$path}");

            return false;
        }

        file_put_contents($path, $contents);

        return true;
    }

    /**
     * Get the destination path for the generated file.
     */
    protected function getDestinationPath(Module $module, string $subPath, string $name, string $suffix = ''): string
    {
        return $module->getAppPath($subPath).'/'.$name.$suffix.'.php';
    }
}
