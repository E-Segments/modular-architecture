<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishAssetsCommand extends Command
{
    protected $signature = 'modular:publish-assets
                            {module? : The name of the module}
                            {--all : Publish assets for all enabled modules}
                            {--force : Overwrite existing assets}';

    protected $description = 'Publish module assets to the public directory';

    public function __construct(
        protected readonly Filesystem $files,
    ) {
        parent::__construct();
    }

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $publishAll = $this->option('all');

        if (! $moduleName && ! $publishAll) {
            $this->components->error('Please specify a module name or use --all to publish all modules.');

            return Command::FAILURE;
        }

        if ($publishAll) {
            return $this->publishAllModules($modular);
        }

        return $this->publishModule($modular, $moduleName);
    }

    protected function publishAllModules(Modular $modular): int
    {
        $modules = $modular->enabled();
        $published = 0;
        $skipped = 0;

        foreach ($modules as $module) {
            $result = $this->publishModuleAssets($module);

            if ($result === true) {
                $published++;
            } elseif ($result === null) {
                $skipped++;
            }
        }

        $this->newLine();
        $this->components->info("Published assets for {$published} module(s). Skipped {$skipped} module(s) without assets.");

        return Command::SUCCESS;
    }

    protected function publishModule(Modular $modular, string $name): int
    {
        if (! $modular->exists($name)) {
            $this->components->error("Module [{$name}] does not exist.");

            return Command::FAILURE;
        }

        $module = $modular->find($name);

        $result = $this->publishModuleAssets($module);

        if ($result === null) {
            $this->components->warn("Module [{$name}] has no assets to publish.");

            return Command::SUCCESS;
        }

        if ($result === false) {
            return Command::FAILURE;
        }

        $this->newLine();
        $this->components->info("Assets published successfully for module [{$name}].");

        return Command::SUCCESS;
    }

    /**
     * Publish assets for a single module.
     *
     * @return bool|null true if published, false if failed, null if no assets
     */
    protected function publishModuleAssets(Module $module): ?bool
    {
        $assetsPath = $module->getResourcesPath('assets');

        if (! $this->files->isDirectory($assetsPath)) {
            return null;
        }

        $destinationPath = public_path('modules/'.$module->getAlias());
        $force = $this->option('force');

        // Check if destination exists and we're not forcing
        if ($this->files->isDirectory($destinationPath) && ! $force) {
            $this->components->twoColumnDetail(
                $module->getName(),
                '<fg=yellow>Skipped (use --force to overwrite)</>'
            );

            return null;
        }

        // Remove existing directory if forcing
        if ($this->files->isDirectory($destinationPath) && $force) {
            $this->files->deleteDirectory($destinationPath);
        }

        // Ensure parent directory exists
        $this->files->ensureDirectoryExists(dirname($destinationPath));

        // Copy assets
        $this->files->copyDirectory($assetsPath, $destinationPath);

        $fileCount = count($this->files->allFiles($destinationPath));

        $this->components->twoColumnDetail(
            $module->getName(),
            "<fg=green>Published {$fileCount} file(s)</>"
        );

        return true;
    }
}
