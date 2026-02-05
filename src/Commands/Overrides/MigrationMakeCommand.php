<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands\Overrides;

use Esegments\ModularArchitecture\Commands\Concerns\ModuleAwareMakeCommand;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand as BaseCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Module-aware migration generator command.
 *
 * Extends Laravel's make:migration command to support --module option.
 */
class MigrationMakeCommand extends BaseCommand
{
    use ModuleAwareMakeCommand;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (! $this->validateModule()) {
            return;
        }

        // If module specified, override the path option
        if ($this->hasModule()) {
            $this->input->setOption(
                'path',
                $this->getModuleMigrationPath()
            );
        }

        parent::handle();
    }

    /**
     * Get the migration path for the module.
     */
    protected function getModuleMigrationPath(): string
    {
        $moduleName = $this->getModuleName();
        $modulePath = $this->getModulePath($moduleName);

        // Return relative path from base_path()
        $relativePath = str_replace(base_path().'/', '', $modulePath);

        return $relativePath.'/database/migrations';
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $options[] = ['module', 'm', InputOption::VALUE_OPTIONAL, 'The module to create the migration in'];

        return $options;
    }
}
