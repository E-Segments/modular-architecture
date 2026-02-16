<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Command;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Filesystem\Filesystem;

/**
 * Command Bridge.
 *
 * Auto-discovers and registers Artisan console commands from enabled modules.
 *
 * Discovery path: app/Console/Commands/*.php
 *
 * All classes extending Illuminate\Console\Command are registered.
 */
class CommandBridge extends AbstractBridge
{
    /**
     * Discovered command classes.
     *
     * @var array<string>
     */
    protected array $commands = [];

    public function name(): string
    {
        return 'command';
    }

    protected function configKey(): string
    {
        return 'commands';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Console\\Command';
    }

    protected function componentPath(): string
    {
        return 'app/Console/Commands';
    }

    public function registerModule(Module $module): void
    {
        $commandsPath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($commandsPath)) {
            return;
        }

        $discovered = $this->discoverCommands($module, $commandsPath);

        if (empty($discovered)) {
            return;
        }

        $this->registered->put($module->getName(), [
            'path' => $commandsPath,
            'commands' => $discovered,
        ]);

        $this->commands = array_merge($this->commands, $discovered);

        $this->log("Discovered {$module->getName()} commands", [
            'count' => count($discovered),
        ]);
    }

    public function boot(): void
    {
        if (empty($this->commands)) {
            return;
        }

        // Only register commands when running in console
        if (! $this->app->runningInConsole()) {
            return;
        }

        Artisan::starting(function ($artisan) {
            foreach ($this->commands as $command) {
                if (class_exists($command)) {
                    $artisan->resolve($command);
                }
            }
        });

        $this->log('Registered console commands', [
            'count' => count($this->commands),
        ]);
    }

    /**
     * Discover console commands.
     *
     * @return array<string>
     */
    protected function discoverCommands(Module $module, string $commandsPath): array
    {
        $files = $this->app->make(Filesystem::class);
        $commands = [];
        $namespace = "Modules\\{$module->getName()}\\Console\\Commands";

        foreach ($files->allFiles($commandsPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($commandsPath.'/', '', $file->getPathname());
            $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $commandClass = $namespace.'\\'.$className;

            if (! class_exists($commandClass)) {
                continue;
            }

            // Verify it extends Console Command
            if (! is_subclass_of($commandClass, ConsoleCommand::class)) {
                continue;
            }

            // Skip abstract classes
            $reflection = new \ReflectionClass($commandClass);
            if ($reflection->isAbstract()) {
                continue;
            }

            $commands[] = $commandClass;
        }

        return $commands;
    }

    /**
     * Get all discovered commands.
     *
     * @return array<string>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}
