<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeDtoCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-dto
                            {module : The name of the module}
                            {name : The name of the DTO}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new DTO (Data Transfer Object) class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        // Remove "Data" suffix if provided
        $name = preg_replace('/Data$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('dto', $replacements);

        $path = $this->getDestinationPath($module, 'Data', $name, 'Data');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("DTO [{$name}Data] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Data",
        ]);

        return Command::SUCCESS;
    }
}
