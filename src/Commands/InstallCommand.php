<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\GitHub\ModuleInstaller;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    protected $signature = 'modular:install
                            {source? : GitHub repository (owner/repo) or URL}
                            {--version= : Specific version/tag to install}
                            {--force : Overwrite if module exists}';

    protected $description = 'Install a module from GitHub';

    public function handle(ModuleInstaller $installer, Modular $modular): int
    {
        $source = $this->argument('source');

        if (! $source) {
            $source = text(
                label: 'Enter GitHub repository (owner/repo or URL)',
                placeholder: 'e.g., esegments/blog-module',
                required: true,
            );
        }

        $version = $this->option('version');

        $this->components->info("Installing module from: {$source}");

        if ($version) {
            $this->line("  Version: {$version}");
        }

        try {
            $result = spin(
                callback: fn () => $installer->install($source, $version),
                message: 'Downloading and installing module...',
            );

            if ($result->failed()) {
                $this->components->error($result->error);

                return Command::FAILURE;
            }

            // Save installation metadata
            $installer->saveMetadata($result->path, [
                'source' => $source,
                'version' => $result->version,
                'installed_at' => now()->toIso8601String(),
            ]);

            $this->components->info("Module [{$result->module->getName()}] installed successfully!");
            $this->components->bulletList([
                "Version: {$result->version}",
                "Path: {$result->path}",
                "Source: {$result->source}",
            ]);

            // Run composer dump-autoload reminder
            $this->newLine();
            $this->components->warn('Remember to run: composer dump-autoload');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error('Installation failed: ' . $e->getMessage());

            if ($this->output->isVerbose()) {
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
