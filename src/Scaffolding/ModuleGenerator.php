<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Scaffolding;

use Esegments\ModularArchitecture\Discovery\PathScanner;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleManifest;
use Esegments\ModularArchitecture\Support\Json;
use Illuminate\Filesystem\Filesystem;

class ModuleGenerator
{
    public function __construct(
        protected readonly Filesystem $files,
        protected readonly StubProcessor $stubProcessor,
        protected readonly PathScanner $pathScanner,
        protected readonly array $config = [],
    ) {}

    /**
     * Generate a new module.
     */
    public function generate(string $name, array $options = []): Module
    {
        $name = str($name)->studly()->toString();
        $basePath = $options['path'] ?? $this->pathScanner->getDefaultPath();

        if (! $basePath) {
            throw new \RuntimeException('No valid module path configured');
        }

        $modulePath = rtrim($basePath, '/').'/'.$name;

        if ($this->files->isDirectory($modulePath)) {
            throw new \RuntimeException("Module [{$name}] already exists at {$modulePath}");
        }

        // Create directory structure
        $this->createDirectoryStructure($modulePath, $options);

        // Generate files
        $this->generateFiles($modulePath, $name, $options);

        return Module::fromPath($modulePath);
    }

    /**
     * Create the module directory structure.
     */
    protected function createDirectoryStructure(string $modulePath, array $options = []): void
    {
        $directories = [
            'app/Providers',
        ];

        // Add optional directories based on options
        if ($options['model'] ?? false) {
            $directories[] = 'app/Models';
            $directories[] = 'database/migrations';
            $directories[] = 'database/factories';
            $directories[] = 'database/seeders';
        }

        if ($options['controller'] ?? false) {
            $directories[] = 'app/Http/Controllers';
        }

        if ($options['views'] ?? false) {
            $directories[] = 'resources/views';
        }

        if ($options['routes'] ?? false) {
            $directories[] = 'routes';
        }

        if ($options['config'] ?? false) {
            $directories[] = 'config';
        }

        if ($options['tests'] ?? false) {
            $directories[] = 'tests/Feature';
            $directories[] = 'tests/Unit';
        }

        foreach ($directories as $directory) {
            $this->files->ensureDirectoryExists("{$modulePath}/{$directory}");
        }
    }

    /**
     * Generate module files.
     */
    protected function generateFiles(string $modulePath, string $name, array $options = []): void
    {
        $replacements = StubProcessor::buildReplacements(
            moduleName: $name,
            version: $options['version'] ?? '1.0.0',
            description: $options['description'] ?? '',
            vendor: $options['vendor'] ?? $this->config['vendor'] ?? null,
            author: $options['author'] ?? $this->config['author_name'] ?? null,
            email: $options['email'] ?? $this->config['author_email'] ?? null,
        );

        // Always generate module.json
        $this->generateModuleJson($modulePath, $name, $replacements, $options);

        // Always generate ServiceProvider
        $this->stubProcessor->processAndWrite(
            'module/ServiceProvider.stub',
            "{$modulePath}/app/Providers/{$name}ServiceProvider.php",
            $replacements
        );

        // Generate composer.json for the module
        $this->generateComposerJson($modulePath, $name, $replacements, $options);

        // Optional files
        if ($options['model'] ?? false) {
            $this->stubProcessor->processAndWrite(
                'module/Model.stub',
                "{$modulePath}/app/Models/{$name}.php",
                $replacements
            );
        }

        if ($options['routes'] ?? false) {
            $this->generateRoutes($modulePath, $name, $replacements);
        }
    }

    /**
     * Generate module.json file.
     */
    protected function generateModuleJson(
        string $modulePath,
        string $name,
        array $replacements,
        array $options,
    ): void {
        $manifest = new ModuleManifest(
            name: $name,
            version: $options['version'] ?? '1.0.0',
            alias: strtolower($name),
            description: $options['description'] ?? '',
            priority: $options['priority'] ?? 0,
            providers: [
                "Modules\\{$name}\\Providers\\{$name}ServiceProvider",
            ],
            requires: $options['requires'] ?? [],
        );

        $this->files->put(
            "{$modulePath}/module.json",
            $manifest->toJson()
        );
    }

    /**
     * Generate composer.json for the module.
     */
    protected function generateComposerJson(
        string $modulePath,
        string $name,
        array $replacements,
        array $options,
    ): void {
        $vendor = $replacements['VENDOR'] ?? 'esegments';
        $kebabName = str($name)->kebab()->toString();

        $composer = [
            'name' => "{$vendor}/{$kebabName}",
            'description' => $options['description'] ?? "The {$name} module",
            'type' => 'laravel-module',
            'license' => 'MIT',
            'autoload' => [
                'psr-4' => [
                    "Modules\\{$name}\\" => 'app/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        "Modules\\{$name}\\Providers\\{$name}ServiceProvider",
                    ],
                ],
            ],
        ];

        $this->files->put(
            "{$modulePath}/composer.json",
            Json::encode($composer)
        );
    }

    /**
     * Generate route files.
     */
    protected function generateRoutes(string $modulePath, string $name, array $replacements): void
    {
        if ($this->stubProcessor->stubExists('module/routes/web.stub')) {
            $this->stubProcessor->processAndWrite(
                'module/routes/web.stub',
                "{$modulePath}/routes/web.php",
                $replacements
            );
        } else {
            $this->files->put(
                "{$modulePath}/routes/web.php",
                "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n// {$name} module routes\n"
            );
        }
    }

    /**
     * Delete a module.
     */
    public function delete(string $path): void
    {
        if ($this->files->isDirectory($path)) {
            $this->files->deleteDirectory($path);
        }
    }
}
