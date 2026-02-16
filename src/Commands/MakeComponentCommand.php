<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeComponentCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-component
                            {module : The name of the module}
                            {name : The name of the Blade component}
                            {--view : Create only the view file}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new Blade component class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $replacements = $this->getDefaultReplacements($module, $name);

        // Create view file
        $viewPath = $module->getPath().'/resources/views/components/'.Str::kebab($name).'.blade.php';
        $viewContents = $this->getStubContents('view', $replacements);

        $this->writeFile($viewPath, $viewContents);

        // Create component class (unless --view only)
        if (! $this->option('view')) {
            $contents = $this->getStubContents('component', $replacements);
            $path = $this->getDestinationPath($module, 'View/Components', $name, '');

            if (! $this->writeFile($path, $contents)) {
                return Command::FAILURE;
            }

            $this->components->info("Component [{$name}] created successfully.");
            $this->components->bulletList([
                "Class: {$path}",
                "View: {$viewPath}",
                "Namespace: Modules\\{$module->getName()}\\View\\Components",
            ]);
        } else {
            $this->components->info("Component view [{$name}] created successfully.");
            $this->components->bulletList([
                "View: {$viewPath}",
            ]);
        }

        return Command::SUCCESS;
    }
}
