<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeObserverCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-observer
                            {module : The name of the module}
                            {name : The name of the observer}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new observer class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        // Remove "Observer" suffix if provided
        $name = preg_replace('/Observer$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('observer', $replacements);

        $path = $this->getDestinationPath($module, 'Observers', $name, 'Observer');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Observer [{$name}Observer] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Observers",
        ]);

        return Command::SUCCESS;
    }
}
