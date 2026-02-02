<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;

class MakeModuleCommand extends Command
{
    protected $signature = 'modular:make
                            {name? : The name of the module}
                            {--version=1.0.0 : Module version}
                            {--description= : Module description}
                            {--interactive : Use interactive mode}
                            {--model : Generate a model}
                            {--controller : Generate a controller}
                            {--views : Generate views}
                            {--routes : Generate routes}
                            {--config : Generate config}
                            {--tests : Generate tests}
                            {--all : Generate all components}';

    protected $description = 'Create a new module';

    public function handle(Modular $modular): int
    {
        $name = $this->argument('name');
        $interactive = $this->option('interactive') || ! $name;

        if ($interactive) {
            return $this->handleInteractive($modular);
        }

        return $this->handleDirect($modular, $name);
    }

    protected function handleInteractive(Modular $modular): int
    {
        $name = text(
            label: 'What is the module name?',
            placeholder: 'E.g. Blog',
            required: true,
            validate: function (string $value) use ($modular) {
                if (strlen($value) < 2) {
                    return 'Name must be at least 2 characters.';
                }
                if (! preg_match('/^[A-Za-z][a-zA-Z0-9]*$/', $value)) {
                    return 'Name must start with a letter and contain only alphanumeric characters.';
                }
                if ($modular->exists($value)) {
                    return "Module [{$value}] already exists.";
                }

                return null;
            }
        );

        $version = text(
            label: 'Module version?',
            placeholder: '1.0.0',
            default: '1.0.0',
        );

        $description = text(
            label: 'Module description (optional)?',
            placeholder: 'A short description of what the module does',
        );

        $components = multiselect(
            label: 'Select components to generate',
            options: [
                'model' => 'Model',
                'controller' => 'Controller',
                'views' => 'Views',
                'routes' => 'Routes',
                'config' => 'Config',
                'tests' => 'Tests',
            ],
            default: [],
            hint: 'Space to select, Enter to confirm',
        );

        $options = [
            'version' => $version,
            'description' => $description,
        ];

        foreach ($components as $component) {
            $options[$component] = true;
        }

        return $this->createModule($modular, $name, $options);
    }

    protected function handleDirect(Modular $modular, string $name): int
    {
        if ($modular->exists($name)) {
            $this->components->error("Module [{$name}] already exists.");

            return Command::FAILURE;
        }

        $all = $this->option('all');

        $options = [
            'version' => $this->option('version'),
            'description' => $this->option('description') ?? '',
            'model' => $all || $this->option('model'),
            'controller' => $all || $this->option('controller'),
            'views' => $all || $this->option('views'),
            'routes' => $all || $this->option('routes'),
            'config' => $all || $this->option('config'),
            'tests' => $all || $this->option('tests'),
        ];

        return $this->createModule($modular, $name, $options);
    }

    protected function createModule(Modular $modular, string $name, array $options): int
    {
        try {
            $module = $modular->create($name, $options);

            $this->components->info("Module [{$module->getName()}] created successfully.");
            $this->components->bulletList([
                "Path: {$module->getPath()}",
                "Version: {$module->getVersion()}",
                "Namespace: {$module->getNamespace()}",
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
