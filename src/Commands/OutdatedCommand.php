<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\GitHub\ModuleInstaller;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

use function Laravel\Prompts\spin;

class OutdatedCommand extends Command
{
    protected $signature = 'modular:outdated
                            {--json : Output as JSON}';

    protected $description = 'Show outdated modules';

    public function handle(ModuleInstaller $installer, Modular $modular): int
    {
        $modules = $modular->all();

        if ($modules->isEmpty()) {
            $this->components->warn('No modules found.');

            return Command::SUCCESS;
        }

        $updates = [];
        $checked = 0;
        $skipped = 0;

        $this->components->info('Checking for updates...');

        foreach ($modules as $module) {
            try {
                $update = spin(
                    callback: fn () => $installer->checkForUpdates($module->getName()),
                    message: "Checking {$module->getName()}...",
                );

                if ($update) {
                    $updates[$module->getName()] = $update;
                }

                $checked++;
            } catch (\Exception) {
                $skipped++;
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($updates, JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        $this->newLine();

        if (empty($updates)) {
            $this->components->info('All modules are up to date!');
            $this->components->twoColumnDetail('Checked', (string) $checked);
            if ($skipped > 0) {
                $this->components->twoColumnDetail('Skipped (no remote source)', (string) $skipped);
            }

            return Command::SUCCESS;
        }

        $this->components->warn(count($updates) . ' outdated module(s) found:');
        $this->newLine();

        $rows = [];
        foreach ($updates as $name => $update) {
            $rows[] = [
                $name,
                $update['current'],
                "<fg=green>{$update['latest']}</>",
                $update['source'],
            ];
        }

        $this->table(['Module', 'Current', 'Latest', 'Source'], $rows);

        $this->newLine();
        $this->components->info('Run `php artisan modular:update --all` to update all modules.');

        return Command::SUCCESS;
    }
}
