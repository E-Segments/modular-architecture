<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeActionCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-action
                            {module : The name of the module}
                            {name : The name of the action}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new action class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        // Remove "Action" suffix if provided
        $name = preg_replace('/Action$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('action', $replacements);

        $path = $this->getDestinationPath($module, 'Actions', $name, 'Action');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Action [{$name}Action] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Actions",
        ]);

        return Command::SUCCESS;
    }
}
