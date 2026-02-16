<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeScopeCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-scope
                            {module : The name of the module}
                            {name : The name of the query scope}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new Eloquent query scope class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $name = preg_replace('/Scope$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('scope', $replacements);

        $path = $this->getDestinationPath($module, 'Models/Scopes', $name, 'Scope');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Scope [{$name}Scope] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Models\\Scopes",
        ]);

        return Command::SUCCESS;
    }
}
