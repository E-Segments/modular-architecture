<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Translation;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Translation Bridge.
 *
 * Auto-discovers and loads translations from enabled modules.
 *
 * Discovery paths:
 * - lang/*.php (PHP translation files)
 * - lang/*.json (JSON translation files)
 * - lang/{locale}/*.php (Locale-specific files)
 *
 * Translations are namespaced by module:
 * - trans('products::messages.created')
 * - __('products::validation.required')
 */
class TranslationBridge extends AbstractBridge
{
    /**
     * Discovered translation paths.
     *
     * @var array<string, string>
     */
    protected array $langPaths = [];

    /**
     * JSON translation files.
     *
     * @var array<string, array<string>>
     */
    protected array $jsonPaths = [];

    public function name(): string
    {
        return 'translation';
    }

    protected function configKey(): string
    {
        return 'translations';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Translation\\Translator';
    }

    protected function componentPath(): string
    {
        return 'lang';
    }

    public function registerModule(Module $module): void
    {
        $langPath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($langPath)) {
            return;
        }

        $files = $this->app->make(Filesystem::class);
        $discovered = $this->discoverTranslations($files, $langPath);

        if (empty($discovered['php']) && empty($discovered['json'])) {
            return;
        }

        $namespace = $this->getTranslationNamespace($module);

        $this->registered->put($module->getName(), [
            'path' => $langPath,
            'namespace' => $namespace,
            'php_files' => $discovered['php'],
            'json_files' => $discovered['json'],
        ]);

        $this->langPaths[$namespace] = $langPath;

        if (! empty($discovered['json'])) {
            $this->jsonPaths[$module->getName()] = $discovered['json'];
        }

        $this->log("Discovered {$module->getName()} translations", [
            'php_files' => count($discovered['php']),
            'json_files' => count($discovered['json']),
        ]);
    }

    public function boot(): void
    {
        $translator = $this->app->make('translator');

        // Register PHP translation namespaces
        foreach ($this->langPaths as $namespace => $path) {
            $translator->addNamespace($namespace, $path);
        }

        // Register JSON translation paths
        foreach ($this->jsonPaths as $moduleName => $jsonFiles) {
            foreach ($jsonFiles as $jsonFile) {
                $translator->addJsonPath(dirname($jsonFile));
            }
        }

        $this->log('Registered translation namespaces', [
            'namespaces' => count($this->langPaths),
            'json_paths' => count($this->jsonPaths),
        ]);
    }

    /**
     * Discover translation files.
     *
     * @return array{php: array<string>, json: array<string>}
     */
    protected function discoverTranslations(Filesystem $files, string $langPath): array
    {
        $phpFiles = [];
        $jsonFiles = [];

        // Find PHP files (direct and in locale directories)
        foreach ($files->allFiles($langPath) as $file) {
            $extension = $file->getExtension();

            if ($extension === 'php') {
                $phpFiles[] = $file->getPathname();
            } elseif ($extension === 'json') {
                $jsonFiles[] = $file->getPathname();
            }
        }

        return [
            'php' => $phpFiles,
            'json' => $jsonFiles,
        ];
    }

    /**
     * Get the translation namespace for a module.
     */
    protected function getTranslationNamespace(Module $module): string
    {
        return Str::kebab($module->getName());
    }

    /**
     * Get all discovered language paths.
     *
     * @return array<string, string>
     */
    public function getLangPaths(): array
    {
        return $this->langPaths;
    }

    /**
     * Get all discovered JSON paths.
     *
     * @return array<string, array<string>>
     */
    public function getJsonPaths(): array
    {
        return $this->jsonPaths;
    }
}
