<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeMiddlewareCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-middleware
                            {module : The name of the module}
                            {name : The name of the middleware}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new middleware class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $name = preg_replace('/Middleware$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('middleware', $replacements);

        $path = $this->getDestinationPath($module, 'Http/Middleware', $name, 'Middleware');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Middleware [{$name}Middleware] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Http\\Middleware",
        ]);

        return Command::SUCCESS;
    }
}
