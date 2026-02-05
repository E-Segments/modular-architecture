<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands\Overrides;

use Esegments\ModularArchitecture\Commands\Concerns\ModuleAwareMakeCommand;
use Illuminate\Database\Console\Factories\FactoryMakeCommand as BaseCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Module-aware factory generator command.
 *
 * Extends Laravel's make:factory command to support --module option.
 */
class FactoryMakeCommand extends BaseCommand
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
            return $this->getModuleNamespace($this->getModuleName()).'\\Database\\Factories';
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
            $namespace = $this->getModuleNamespace($moduleName).'\\Database\\Factories';
            $className = str_replace($namespace.'\\', '', $name);
            $className = str_replace('\\', '/', $className);

            return $this->getModuleFilePath($moduleName, 'factory').'/'.$className.'.php';
        }

        return parent::getPath($name);
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath($stub): string
    {
        return parent::resolveStubPath($stub);
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $options[] = ['module', 'm', InputOption::VALUE_OPTIONAL, 'The module to create the factory in'];

        return $options;
    }
}
