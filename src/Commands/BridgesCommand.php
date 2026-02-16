<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Bridges\BridgeManager;
use Illuminate\Console\Command;

class BridgesCommand extends Command
{
    protected $signature = 'modular:bridges
                            {--enabled : Show only enabled bridges}
                            {--available : Show only available bridges}';

    protected $description = 'List all framework bridges and their status';

    public function handle(BridgeManager $manager): int
    {
        $bridges = $manager->all();
        $showEnabled = $this->option('enabled');
        $showAvailable = $this->option('available');

        $this->components->info('Framework Bridges');
        $this->newLine();

        $rows = [];
        $totalModules = 0;
        $enabledCount = 0;
        $availableCount = 0;

        foreach ($bridges as $name => $bridge) {
            $available = $bridge->isAvailable();
            $enabled = $bridge->isEnabled();
            $componentCount = 0;

            if ($bridge instanceof AbstractBridge) {
                $componentCount = $bridge->getRegistered()->count();
            }

            // Apply filters
            if ($showEnabled && ! $enabled) {
                continue;
            }
            if ($showAvailable && ! $available) {
                continue;
            }

            $availableStatus = $available
                ? '<fg=green>✓</>'
                : '<fg=red>✗</>';

            $enabledStatus = $enabled
                ? '<fg=green>Enabled</>'
                : '<fg=gray>Disabled</>';

            $rows[] = [
                $bridge->name(),
                $availableStatus,
                $enabledStatus,
                $componentCount > 0 ? $componentCount : '-',
            ];

            $totalModules += $componentCount;
            if ($enabled) {
                $enabledCount++;
            }
            if ($available) {
                $availableCount++;
            }
        }

        $this->table(
            ['Bridge', 'Available', 'Status', 'Modules'],
            $rows
        );

        $this->newLine();

        // Summary
        $this->components->twoColumnDetail('Total bridges', (string) $bridges->count());
        $this->components->twoColumnDetail('Available', (string) $availableCount);
        $this->components->twoColumnDetail('Enabled', (string) $enabledCount);
        $this->components->twoColumnDetail('Modules registered', (string) $totalModules);

        // Cache status
        $cache = $manager->getCache();
        if ($cache) {
            $this->newLine();
            $stats = $cache->stats();
            $cacheStatus = $stats['enabled']
                ? ($stats['has_cache'] ? '<fg=green>Cached</>' : '<fg=yellow>Not cached</>')
                : '<fg=gray>Disabled</>';
            $this->components->twoColumnDetail('Cache', $cacheStatus);
        }

        // Help text
        $this->newLine();
        $this->line('  Use <comment>modular:bridges:inspect {bridge}</comment> for detailed information');

        return Command::SUCCESS;
    }
}
