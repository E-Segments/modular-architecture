<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class RemoveCommand extends Command
{
    protected $signature = 'modular:remove
                            {name : Module name to remove}
                            {--force : Force removal even if other modules depend on it}';

    protected $description = 'Remove a module';

    public function handle(Modular $modular): int
    {
        $name = $this->argument('name');
        $force = $this->option('force');

        if (! $modular->exists($name)) {
            $this->components->error("Module [{$name}] not found.");

            return Command::FAILURE;
        }

        $module = $modular->findOrFail($name);

        // Check if protected
        if ($module->isProtected()) {
            $this->components->error("Cannot remove protected module [{$name}].");

            return Command::FAILURE;
        }

        // Check dependents
        $dependents = $modular->getDependents($name);
        if ($dependents->isNotEmpty() && ! $force) {
            $this->components->error("Cannot remove [{$name}] - the following modules depend on it:");
            $this->components->bulletList($dependents->names());
            $this->newLine();
            $this->line('Use --force to remove anyway.');

            return Command::FAILURE;
        }

        // Confirm deletion
        $confirmed = confirm(
            label: "Are you sure you want to permanently remove module [{$name}]?",
            default: false,
        );

        if (! $confirmed) {
            $this->components->warn('Operation cancelled.');

            return Command::SUCCESS;
        }

        try {
            $path = $module->getPath();
            $modular->delete($name, $force);

            $this->components->info("Module [{$name}] removed successfully.");
            $this->line("Deleted: {$path}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
