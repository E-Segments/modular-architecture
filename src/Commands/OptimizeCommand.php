<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class OptimizeCommand extends Command
{
    protected $signature = 'modular:optimize';

    protected $description = 'Optimize modules for production';

    public function handle(Modular $modular): int
    {
        $this->components->info('Optimizing modules for production...');

        // Clear existing caches
        $this->call('modular:cache:clear');

        // Validate all modules
        $this->newLine();
        $result = $modular->validateAll();

        if (! $result->isValid()) {
            $this->components->error('Validation failed - fix errors before optimizing:');
            foreach ($result->errors as $error) {
                $this->components->bulletList([$error]);
            }

            return Command::FAILURE;
        }

        // Build cache
        $this->newLine();
        $this->call('modular:cache');

        $this->newLine();
        $this->components->info('Optimization complete!');
        $this->components->bulletList([
            "Modules: {$modular->count()} total, {$modular->enabledCount()} enabled",
            'Cache: Built and ready',
            'Validation: Passed',
        ]);

        return Command::SUCCESS;
    }
}
