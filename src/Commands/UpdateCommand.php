<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\GitHub\ModuleInstaller;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;

class UpdateCommand extends Command
{
    protected $signature = 'modular:update
                            {name? : Module name to update}
                            {--version= : Specific version to update to}
                            {--all : Update all modules}
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Update installed modules';

    public function handle(ModuleInstaller $installer, Modular $modular): int
    {
        $name = $this->argument('name');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');
        $version = $this->option('version');

        if (! $name && ! $all) {
            $this->components->error('Please specify a module name or use --all');

            return Command::FAILURE;
        }

        if ($all) {
            return $this->updateAll($installer, $modular, $dryRun);
        }

        return $this->updateModule($installer, $modular, $name, $version, $dryRun);
    }

    protected function updateModule(
        ModuleInstaller $installer,
        Modular $modular,
        string $name,
        ?string $version,
        bool $dryRun,
    ): int {
        if (! $modular->exists($name)) {
            $this->components->error("Module [{$name}] not found.");

            return Command::FAILURE;
        }

        $update = $installer->checkForUpdates($name);

        if (! $update && ! $version) {
            $this->components->info("Module [{$name}] is already up to date.");

            return Command::SUCCESS;
        }

        if ($update) {
            $this->components->info("Update available for [{$name}]:");
            $this->components->twoColumnDetail('Current', $update['current']);
            $this->components->twoColumnDetail('Latest', $update['latest']);
        }

        if ($dryRun) {
            $this->components->warn('Dry run - no changes made.');

            return Command::SUCCESS;
        }

        $targetVersion = $version ?? $update['latest'] ?? null;

        if (! confirm("Update [{$name}] to version {$targetVersion}?")) {
            $this->components->warn('Update cancelled.');

            return Command::SUCCESS;
        }

        try {
            $result = spin(
                callback: fn () => $installer->update($name, $targetVersion),
                message: "Updating {$name}...",
            );

            if ($result->failed()) {
                $this->components->error($result->error);

                return Command::FAILURE;
            }

            // Save updated metadata
            $installer->saveMetadata($result->path, [
                'source' => $result->source,
                'version' => $result->version,
                'updated_at' => now()->toIso8601String(),
            ]);

            $this->components->info("Module [{$name}] updated to {$result->version}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error('Update failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function updateAll(ModuleInstaller $installer, Modular $modular, bool $dryRun): int
    {
        $this->components->info('Checking for updates...');

        $updates = [];

        foreach ($modular->all() as $module) {
            $update = $installer->checkForUpdates($module->getName());
            if ($update) {
                $updates[$module->getName()] = $update;
            }
        }

        if (empty($updates)) {
            $this->components->info('All modules are up to date.');

            return Command::SUCCESS;
        }

        $this->components->info('Updates available:');
        $this->newLine();

        $rows = [];
        foreach ($updates as $name => $update) {
            $rows[] = [$name, $update['current'], $update['latest']];
        }

        $this->table(['Module', 'Current', 'Latest'], $rows);

        if ($dryRun) {
            $this->newLine();
            $this->components->warn('Dry run - no changes made.');

            return Command::SUCCESS;
        }

        if (! confirm('Update all modules?')) {
            $this->components->warn('Update cancelled.');

            return Command::SUCCESS;
        }

        $success = 0;
        $failed = 0;

        foreach ($updates as $name => $update) {
            try {
                $result = spin(
                    callback: fn () => $installer->update($name),
                    message: "Updating {$name}...",
                );

                if ($result->isSuccessful()) {
                    $installer->saveMetadata($result->path, [
                        'source' => $result->source,
                        'version' => $result->version,
                        'updated_at' => now()->toIso8601String(),
                    ]);
                    $success++;
                } else {
                    $failed++;
                    $this->components->error("Failed to update {$name}: {$result->error}");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->components->error("Failed to update {$name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->components->info("Updated: {$success}, Failed: {$failed}");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
