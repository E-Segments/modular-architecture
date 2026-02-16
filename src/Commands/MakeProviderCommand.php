<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeProviderCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-provider
                            {module : The name of the module}
                            {name : The name of the service provider}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new service provider class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $name = preg_replace('/ServiceProvider$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('provider', $replacements);

        $path = $this->getDestinationPath($module, 'Providers', $name, 'ServiceProvider');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Provider [{$name}ServiceProvider] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Providers",
        ]);
        $this->components->warn("Don't forget to register the provider in your module's main service provider.");

        return Command::SUCCESS;
    }
}
