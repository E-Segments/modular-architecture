<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeExceptionCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-exception
                            {module : The name of the module}
                            {name : The name of the exception}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new exception class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $name = preg_replace('/Exception$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('exception', $replacements);

        $path = $this->getDestinationPath($module, 'Exceptions', $name, 'Exception');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Exception [{$name}Exception] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Exceptions",
        ]);

        return Command::SUCCESS;
    }
}
