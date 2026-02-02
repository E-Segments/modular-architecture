<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class CacheCommand extends Command
{
    protected $signature = 'modular:cache';

    protected $description = 'Build module discovery cache for production';

    public function handle(Modular $modular): int
    {
        $this->components->info('Building module cache...');

        try {
            $modular->cache();

            $count = $modular->enabledCount();
            $this->components->info("Module cache built successfully. ({$count} modules cached)");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error('Failed to build cache: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
