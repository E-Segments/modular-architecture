<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class DependentsCommand extends Command
{
    protected $signature = 'modular:dependents {name : Module name to check}';

    protected $description = 'Show modules that depend on the given module';

    public function handle(Modular $modular): int
    {
        $name = $this->argument('name');

        if (! $modular->exists($name)) {
            $this->components->error("Module [{$name}] not found.");

            return Command::FAILURE;
        }

        $module = $modular->findOrFail($name);
        $dependents = $modular->getDependents($name);

        $this->components->info("Dependents of [{$name}] v{$module->getVersion()}:");
        $this->newLine();

        if ($dependents->isEmpty()) {
            $this->components->warn('No modules depend on this module.');

            return Command::SUCCESS;
        }

        foreach ($dependents as $dependent) {
            $requires = $dependent->getRequires();
            $constraint = $requires[$name] ?? '*';
            $status = $dependent->isEnabled() ? '<fg=green>enabled</>' : '<fg=yellow>disabled</>';

            $this->line("  • {$dependent->getName()} (requires {$name} {$constraint}) [{$status}]");
        }

        $this->newLine();
        $this->components->info("Total: {$dependents->count()} module(s) depend on [{$name}]");

        return Command::SUCCESS;
    }
}
