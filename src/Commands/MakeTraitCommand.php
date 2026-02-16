<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeTraitCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-trait
                            {module : The name of the module}
                            {name : The name of the trait}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new trait for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('trait', $replacements);

        $path = $this->getDestinationPath($module, 'Concerns', $name, '');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Trait [{$name}] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Concerns",
        ]);

        return Command::SUCCESS;
    }
}
