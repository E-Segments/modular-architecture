<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands\Overrides;

use Esegments\ModularArchitecture\Commands\Concerns\ModuleAwareMakeCommand;
use Illuminate\Database\Console\Seeds\SeederMakeCommand as BaseCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Module-aware seeder generator command.
 *
 * Extends Laravel's make:seeder command to support --module option.
 */
class SeederMakeCommand extends BaseCommand
{
    use ModuleAwareMakeCommand;

    /**
     * Execute the console command.
     */
    public function handle(): ?bool
    {
        if (! $this->validateModule()) {
            return false;
        }

        return parent::handle();
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        if ($this->hasModule()) {
            return $this->getModuleNamespace($this->getModuleName()).'\\Database\\Seeders';
        }

        return parent::getDefaultNamespace($rootNamespace);
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        if ($this->hasModule()) {
            return $this->getModuleNamespace($this->getModuleName()).'\\';
        }

        return parent::rootNamespace();
    }

    /**
     * Get the destination class path.
     */
    protected function getPath($name): string
    {
        if ($this->hasModule()) {
            $moduleName = $this->getModuleName();
            $namespace = $this->getModuleNamespace($moduleName).'\\Database\\Seeders';
            $className = str_replace($namespace.'\\', '', $name);
            $className = str_replace('\\', '/', $className);

            return $this->getModuleFilePath($moduleName, 'seeder').'/'.$className.'.php';
        }

        return parent::getPath($name);
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $options[] = ['module', 'm', InputOption::VALUE_OPTIONAL, 'The module to create the seeder in'];

        return $options;
    }
}
