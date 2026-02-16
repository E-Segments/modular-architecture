<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeHelperCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-helper
                            {module : The name of the module}
                            {name : The name of the helper class}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new helper class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $name = preg_replace('/Helper$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('helper', $replacements);

        $path = $this->getDestinationPath($module, 'Helpers', $name, 'Helper');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Helper [{$name}Helper] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Helpers",
        ]);

        return Command::SUCCESS;
    }
}
