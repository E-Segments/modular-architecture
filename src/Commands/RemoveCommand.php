<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\LaravelExtensions\Facades\Extensions;
use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleUninstall;
use Esegments\ModularArchitecture\Extensions\Points\ModuleUninstalled;
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

        // Dispatch BeforeModuleUninstall extension point (interruptible)
        $beforeUninstall = new BeforeModuleUninstall($module, $name, $force);
        $canProceed = Extensions::dispatchInterruptible($beforeUninstall);

        if (! $canProceed) {
            $handler = $beforeUninstall->getInterruptedBy();
            $this->components->error('Removal was cancelled by an extension handler.');
            if ($handler) {
                $this->line("  Interrupted by: {$handler}");
            }

            return Command::FAILURE;
        }

        if ($beforeUninstall->hasErrors()) {
            $this->components->error('Removal blocked:');
            foreach ($beforeUninstall->errors as $error) {
                $this->line("  - {$error}");
            }

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

            // Dispatch ModuleUninstalled extension point (graceful - errors shouldn't block completed removal)
            Extensions::gracefully()->dispatch(new ModuleUninstalled($name, $path));

            $this->components->info("Module [{$name}] removed successfully.");
            $this->line("Deleted: {$path}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
