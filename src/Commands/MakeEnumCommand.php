<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeEnumCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-enum
                            {module : The name of the module}
                            {name : The name of the enum}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new enum class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('enum', $replacements);

        $path = $this->getDestinationPath($module, 'Enums', $name);

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Enum [{$name}] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Enums",
        ]);

        return Command::SUCCESS;
    }
}
