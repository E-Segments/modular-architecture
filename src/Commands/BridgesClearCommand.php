<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Bridges\BridgeManager;
use Illuminate\Console\Command;

class BridgesClearCommand extends Command
{
    protected $signature = 'modular:bridges:clear';

    protected $description = 'Clear the bridge cache';

    public function handle(BridgeManager $manager): int
    {
        $cache = $manager->getCache();

        if (! $cache) {
            $this->components->warn('Bridge cache is not configured.');

            return Command::SUCCESS;
        }

        if (! $cache->has()) {
            $this->components->info('Bridge cache is already empty.');

            return Command::SUCCESS;
        }

        $manager->clearCache();

        $this->components->info('Bridge cache cleared successfully.');

        return Command::SUCCESS;
    }
}
