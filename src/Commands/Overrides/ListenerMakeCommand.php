<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands\Overrides;

use Esegments\ModularArchitecture\Commands\Concerns\ModuleAwareMakeCommand;
use Illuminate\Foundation\Console\ListenerMakeCommand as BaseCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Module-aware listener generator command.
 *
 * Extends Laravel's make:listener command to support --module option.
 */
class ListenerMakeCommand extends BaseCommand
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
            return $this->getModuleDefaultNamespace($this->getModuleName(), 'listener');
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
            $namespace = $this->getModuleDefaultNamespace($moduleName, 'listener');
            $className = str_replace($namespace.'\\', '', $name);
            $className = str_replace('\\', '/', $className);

            return $this->getModuleFilePath($moduleName, 'listener').'/'.$className.'.php';
        }

        return parent::getPath($name);
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $options[] = ['module', 'm', InputOption::VALUE_OPTIONAL, 'The module to create the listener in'];

        return $options;
    }
}
