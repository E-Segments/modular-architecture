<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Bridges\BridgeManager;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class BridgesCacheCommand extends Command
{
    protected $signature = 'modular:bridges:cache';

    protected $description = 'Cache discovered bridge components for production';

    public function handle(BridgeManager $manager, Modular $modular): int
    {
        $cache = $manager->getCache();

        if (! $cache) {
            $this->components->error('Bridge cache is not configured.');

            return Command::FAILURE;
        }

        if (! $cache->isEnabled()) {
            $this->components->error('Bridge caching is disabled in configuration.');
            $this->line('Enable it with: MODULAR_BRIDGE_CACHE=true');

            return Command::FAILURE;
        }

        $this->components->info('Caching bridge components...');

        // Register all modules with bridges
        $modules = $modular->enabled();
        $enabledBridges = $manager->enabled();

        if ($enabledBridges->isEmpty()) {
            $this->components->warn('No bridges are enabled.');

            return Command::SUCCESS;
        }

        $totalComponents = 0;

        foreach ($modules as $module) {
            $manager->registerModule($module);
        }

        // Save to cache
        $manager->saveToCache();

        // Count cached components
        foreach ($enabledBridges as $bridge) {
            if ($bridge instanceof \Esegments\ModularArchitecture\Bridges\AbstractBridge) {
                $totalComponents += $bridge->getRegistered()->count();
            }
        }

        $this->newLine();
        $this->components->info('Bridge cache created successfully!');
        $this->newLine();

        $this->components->twoColumnDetail('Bridges cached', (string) $enabledBridges->count());
        $this->components->twoColumnDetail('Modules processed', (string) $modules->count());
        $this->components->twoColumnDetail('Components cached', (string) $totalComponents);

        $stats = $cache->stats();
        if ($stats['path']) {
            $this->components->twoColumnDetail('Cache file', $stats['path']);
        }

        return Command::SUCCESS;
    }
}
