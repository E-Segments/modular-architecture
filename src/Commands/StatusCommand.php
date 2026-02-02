<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class StatusCommand extends Command
{
    protected $signature = 'modular:status {name? : Module name for detailed status}';

    protected $description = 'Show module status and statistics';

    public function handle(Modular $modular): int
    {
        $name = $this->argument('name');

        if ($name) {
            return $this->showModuleStatus($modular, $name);
        }

        return $this->showOverview($modular);
    }

    protected function showOverview(Modular $modular): int
    {
        $all = $modular->all();
        $enabled = $modular->enabled();
        $disabled = $modular->disabled();

        $this->components->info('Module Overview');
        $this->newLine();

        $this->components->twoColumnDetail('Total Modules', (string) $all->count());
        $this->components->twoColumnDetail('Enabled', "<fg=green>{$enabled->count()}</>");
        $this->components->twoColumnDetail('Disabled', "<fg=yellow>{$disabled->count()}</>");
        $this->components->twoColumnDetail('Protected', (string) $all->protected()->count());

        $this->newLine();

        // Validate all modules
        $result = $modular->validateAll();

        if ($result->isValid()) {
            $this->components->info('✓ All modules are valid');
        } else {
            $this->components->error('✗ Validation errors found');
            foreach ($result->errors as $error) {
                $this->components->bulletList([$error]);
            }
        }

        if ($result->hasWarnings()) {
            $this->newLine();
            $this->components->warn('Warnings:');
            foreach ($result->warnings as $warning) {
                $this->components->bulletList([$warning]);
            }
        }

        return Command::SUCCESS;
    }

    protected function showModuleStatus(Modular $modular, string $name): int
    {
        $module = $modular->find($name);

        if (! $module) {
            $this->components->error("Module [{$name}] not found.");

            return Command::FAILURE;
        }

        $this->components->info("Module: {$module->getName()}");
        $this->newLine();

        $this->components->twoColumnDetail('Version', $module->getVersion());
        $this->components->twoColumnDetail('Description', $module->getDescription() ?: '-');
        $this->components->twoColumnDetail('Path', $module->getPath());
        $this->components->twoColumnDetail('Namespace', $module->getNamespace());
        $this->components->twoColumnDetail('Priority', (string) $module->getPriority());
        $this->components->twoColumnDetail(
            'Status',
            $module->isEnabled() ? '<fg=green>Enabled</>' : '<fg=yellow>Disabled</>'
        );
        $this->components->twoColumnDetail(
            'Protected',
            $module->isProtected() ? '<fg=blue>Yes</>' : 'No'
        );

        // Dependencies
        $requires = $module->getRequires();
        if (! empty($requires)) {
            $this->newLine();
            $this->components->info('Dependencies:');
            foreach ($requires as $dep => $constraint) {
                $depModule = $modular->find($dep);
                $status = $depModule
                    ? ($depModule->isEnabled() ? '<fg=green>✓</>' : '<fg=yellow>○</>')
                    : '<fg=red>✗</>';
                $this->components->twoColumnDetail("  {$dep}", "{$constraint} {$status}");
            }
        }

        // Dependents
        $dependents = $modular->getDependents($name);
        if ($dependents->isNotEmpty()) {
            $this->newLine();
            $this->components->info('Depended on by:');
            foreach ($dependents as $dependent) {
                $this->components->bulletList([$dependent->getName()]);
            }
        }

        // Providers
        $providers = $module->getProviders();
        if (! empty($providers)) {
            $this->newLine();
            $this->components->info('Service Providers:');
            foreach ($providers as $provider) {
                $exists = class_exists($provider) ? '<fg=green>✓</>' : '<fg=red>✗</>';
                $this->components->bulletList(["{$provider} {$exists}"]);
            }
        }

        // Validation
        $this->newLine();
        $result = $modular->validate($name);
        if ($result->isValid()) {
            $this->components->info('✓ Module is valid');
        } else {
            $this->components->error('✗ Validation errors:');
            foreach ($result->errors as $error) {
                $this->components->bulletList([$error]);
            }
        }

        return Command::SUCCESS;
    }
}
