<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class DisableCommand extends Command
{
    protected $signature = 'modular:disable {name : Module name to disable}';

    protected $description = 'Disable a module';

    public function handle(Modular $modular): int
    {
        $name = $this->argument('name');

        if (! $modular->exists($name)) {
            $this->components->error("Module [{$name}] not found.");

            return Command::FAILURE;
        }

        if (! $modular->isEnabled($name)) {
            $this->components->warn("Module [{$name}] is already disabled.");

            return Command::SUCCESS;
        }

        if ($modular->isProtected($name)) {
            $this->components->error("Cannot disable protected module [{$name}].");

            return Command::FAILURE;
        }

        // Check for dependents
        $dependents = $modular->getDependents($name)->enabled();
        if ($dependents->isNotEmpty()) {
            $this->components->error("Cannot disable [{$name}] - the following modules depend on it:");
            $this->components->bulletList($dependents->names());

            return Command::FAILURE;
        }

        try {
            $modular->disable($name);
            $this->components->info("Module [{$name}] disabled successfully.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
