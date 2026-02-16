<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeInterfaceCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-interface
                            {module : The name of the module}
                            {name : The name of the interface}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new interface for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $name = preg_replace('/Contract$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('interface', $replacements);

        $path = $this->getDestinationPath($module, 'Contracts', $name, 'Contract');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Interface [{$name}Contract] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Contracts",
        ]);

        return Command::SUCCESS;
    }
}
