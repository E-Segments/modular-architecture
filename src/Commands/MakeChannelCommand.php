<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Commands\Concerns\GeneratesModuleFiles;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class MakeChannelCommand extends Command
{
    use GeneratesModuleFiles;

    protected $signature = 'modular:make-channel
                            {module : The name of the module}
                            {name : The name of the broadcast channel}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new broadcast channel class for a module';

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $name = $this->argument('name');

        $module = $this->getModule($modular, $moduleName);

        if (! $module) {
            return Command::FAILURE;
        }

        $name = preg_replace('/Channel$/', '', $name);

        $replacements = $this->getDefaultReplacements($module, $name);
        $contents = $this->getStubContents('channel', $replacements);

        $path = $this->getDestinationPath($module, 'Broadcasting', $name, 'Channel');

        if (! $this->writeFile($path, $contents)) {
            return Command::FAILURE;
        }

        $this->components->info("Channel [{$name}Channel] created successfully.");
        $this->components->bulletList([
            "Path: {$path}",
            "Namespace: Modules\\{$module->getName()}\\Broadcasting",
        ]);

        return Command::SUCCESS;
    }
}
