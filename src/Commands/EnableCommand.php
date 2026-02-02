<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class EnableCommand extends Command
{
    protected $signature = 'modular:enable {name : Module name to enable}';

    protected $description = 'Enable a module';

    public function handle(Modular $modular): int
    {
        $name = $this->argument('name');

        if (! $modular->exists($name)) {
            $this->components->error("Module [{$name}] not found.");

            return Command::FAILURE;
        }

        if ($modular->isEnabled($name)) {
            $this->components->warn("Module [{$name}] is already enabled.");

            return Command::SUCCESS;
        }

        try {
            $modular->enable($name);
            $this->components->info("Module [{$name}] enabled successfully.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
