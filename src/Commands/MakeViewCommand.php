<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeViewCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-view
                            {module : The name of the module}
                            {name : The name of the view (e.g., users.index)}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new Blade view file for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        // Convert dot notation to path
        $viewPath = str_replace('.', '/', $name);
        $path = $module->getPath().'/resources/views/'.$viewPath.'.blade.php';

        $replacements = [
            'CLASS_NAME' => Str::studly(str_replace(['/', '.'], ' ', $name)),
        ];
        $contents = $this->getStubContents('view', $replacements);

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("View [{$name}] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Usage: @include('{$module->getAlias()}::{$name}')",
        ]);

        return Command::SUCCESS;
    }
}
