<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands\Overrides;

use Esegments\ModularArchitecture\Commands\Concerns\ModuleAwareMakeCommand;
use Illuminate\Foundation\Console\TestMakeCommand as BaseCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Module-aware test generator command.
 *
 * Extends Laravel's make:test command to support --module option.
 */
class TestMakeCommand extends BaseCommand
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
            $moduleName = $this->getModuleName();
            $baseNamespace = $this->getModuleNamespace($moduleName).'\\Tests';

            if ($this->option('unit')) {
                return $baseNamespace.'\\Unit';
            }

            return $baseNamespace.'\\Feature';
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
            $type = $this->option('unit') ? 'test-unit' : 'test-feature';
            $basePath = $this->getModuleFilePath($moduleName, $type);

            $testNamespace = $this->option('unit')
                ? $this->getModuleNamespace($moduleName).'\\Tests\\Unit'
                : $this->getModuleNamespace($moduleName).'\\Tests\\Feature';

            $className = str_replace($testNamespace.'\\', '', $name);
            $className = str_replace('\\', '/', $className);

            return $basePath.'/'.$className.'.php';
        }

        return parent::getPath($name);
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $options[] = ['module', 'm', InputOption::VALUE_OPTIONAL, 'The module to create the test in'];

        return $options;
    }
}
