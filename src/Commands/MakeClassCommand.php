<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeClassCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-class
                            {module : The name of the module}
                            {name : The name of the class}
                            {--path= : Custom path within the module (default: app)}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');
        $customPath = $this->option('path');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('class', $replacements);

        $subPath = $customPath ?: '';
        $path = $module->getAppPath($subPath).'/'.$name.'.php';

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $namespace = $customPath
            ? "Modules\\{$module->getName()}\\".str_replace('/', '\\', $customPath)
            : "Modules\\{$module->getName()}";

        $this->components->info("Class [{$name}] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: {$namespace}",
        ]);

        return Command::SUCCESS;
    }
}
