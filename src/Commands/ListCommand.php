<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Console\Command;

class ListCommand extends Command
{
    protected $signature = 'modular:list
                            {--enabled : Show only enabled modules}
                            {--disabled : Show only disabled modules}';

    protected $description = 'List all discovered modules';

    public function handle(Modular $modular): int
    {
        $modules = match (true) {
            $this->option('enabled') => $modular->enabled(),
            $this->option('disabled') => $modular->disabled(),
            default => $modular->all(),
        };

        if ($modules->isEmpty()) {
            $this->components->warn('No modules found.');

            return self::SUCCESS;
        }

        $headers = ['Module', 'Version', 'Status', 'Protected', 'Dependencies'];
        $rows = $modules
            ->sortByName()
            ->map(fn (Module $module) => [
                $module->getName(),
                $module->getVersion(),
                $module->isEnabled() ? '✓ Enabled' : '○ Disabled',
                $module->isProtected() ? 'Yes' : '-',
                implode(', ', array_keys($module->getRequires())) ?: '-',
            ])
            ->values()
            ->all();

        $this->table($headers, $rows);

        $stats = $modular->stats();
        $this->newLine();
        $this->components->info("Total: {$stats['total']} | Enabled: {$stats['enabled']} | Disabled: {$stats['disabled']} | Protected: {$stats['protected']}");

        return self::SUCCESS;
    }
}
