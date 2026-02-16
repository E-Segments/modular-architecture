<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeServiceCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-service
                            {module : The name of the module}
                            {name : The name of the service}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new service class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        // Remove "Service" suffix if provided
        $name = preg_replace('/Service$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('service', $replacements);

        $path = $this->getDestinationPath($module, 'Services', $name, 'Service');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Service [{$name}Service] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Services",
        ]);

        return Command::SUCCESS;
    }
}
