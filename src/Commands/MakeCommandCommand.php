<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeCommandCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-command
                            {module : The name of the module}
                            {name : The name of the console command}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new Artisan console command for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $name = preg_replace('/Command$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('command', $replacements);

        $path = $this->getDestinationPath($module, 'Console/Commands', $name, 'Command');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Command [{$name}Command] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Console\\Commands",
        ]);

        return Command::SUCCESS;
    }
}
