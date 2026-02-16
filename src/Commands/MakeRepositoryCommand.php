<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeRepositoryCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-repository
                            {module : The name of the module}
                            {name : The name of the repository}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new repository class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        // Remove "Repository" suffix if provided
        $name = preg_replace('/Repository$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('repository', $replacements);

        $path = $this->getDestinationPath($module, 'Repositories', $name, 'Repository');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Repository [{$name}Repository] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Repositories",
        ]);

        return Command::SUCCESS;
    }
}
