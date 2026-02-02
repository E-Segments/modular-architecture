<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class ListCommand extends Command
{
    protected $signature = 'modular:list
                            {--enabled : Show only enabled modules}
                            {--disabled : Show only disabled modules}';

    protected $description = 'List all modules';

    public function handle(Modular $modular): int
    {
        $modules = match (true) {
            $this->option('enabled') => $modular->enabled(),
            $this->option('disabled') => $modular->disabled(),
            default => $modular->all(),
        };

        if ($modules->isEmpty()) {
            $this->components->warn('No modules found.');

            return Command::SUCCESS;
        }

        $rows = $modules->map(fn ($module) => [
            $module->getName(),
            $module->getVersion(),
            $module->isEnabled() ? '<fg=green>Enabled</>' : '<fg=yellow>Disabled</>',
            $module->isProtected() ? '✓' : '',
            implode(', ', array_keys($module->getRequires())) ?: '-',
        ])->values()->all();

        $this->table(
            ['Module', 'Version', 'Status', 'Protected', 'Dependencies'],
            $rows
        );

        $this->newLine();
        $this->components->info("Total: {$modular->count()} modules ({$modular->enabledCount()} enabled)");

        return Command::SUCCESS;
    }
}
