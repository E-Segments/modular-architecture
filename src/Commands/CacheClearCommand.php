<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class CacheClearCommand extends Command
{
    protected $signature = 'modular:cache:clear';

    protected $description = 'Clear all module caches';

    public function handle(Modular $modular): int
    {
        $modular->clearCache();

        $this->components->info('Module caches cleared successfully.');

        return Command::SUCCESS;
    }
}
