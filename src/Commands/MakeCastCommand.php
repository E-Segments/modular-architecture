<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeCastCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-cast
                            {module : The name of the module}
                            {name : The name of the cast}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new Eloquent cast class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $name = preg_replace('/Cast$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('cast', $replacements);

        $path = $this->getDestinationPath($module, 'Casts', $name, 'Cast');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Cast [{$name}Cast] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Casts",
        ]);

        return Command::SUCCESS;
    }
}
