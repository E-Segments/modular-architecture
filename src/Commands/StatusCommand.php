<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Esegments\ModularArchitecture\Module\Module;
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
        $stats = $modular->stats();

        $this->components->info('Module Overview');

        $this->newLine();
        $this->line("  Total Modules:     <fg=cyan>{$stats['total']}</>");
        $this->line("  Enabled:           <fg=green>{$stats['enabled']}</>");
        $this->line("  Disabled:          <fg=yellow>{$stats['disabled']}</>");
        $this->line("  Protected:         <fg=blue>{$stats['protected']}</>");
        $this->newLine();

        // Validate all modules
        $result = $modular->validateAll();

        if ($result->isValid()) {
            $this->components->info('✓ All modules are valid');
        } else {
            $this->components->error('✗ Validation errors found');
            foreach ($result->errors as $err) {
                $this->line("  • {$err}");
            }
        }

        if ($result->hasWarnings()) {
            $this->newLine();
            $this->components->warn('Warnings:');
            foreach ($result->warnings as $warn) {
                $this->line("  • {$warn}");
            }
        }

        // Show quick status table
        $this->newLine();

        $headers = ['Module', 'Version', 'Enabled', 'Protected'];
        $rows = $modular->all()
            ->sortByName()
            ->map(fn (Module $module) => [
                $module->getName(),
                $module->getVersion(),
                $module->isEnabled() ? '✓' : '○',
                $module->isProtected() ? '🔒' : '-',
            ])
            ->values()
            ->all();

        $this->table($headers, $rows);

        return self::SUCCESS;
    }

    protected function showModuleStatus(Modular $modular, string $name): int
    {
        $module = $modular->find($name);

        if (! $module) {
            $this->components->error("Module '{$name}' not found.");

            return self::FAILURE;
        }

        $this->components->info("Module: {$module->getName()}");

        $this->newLine();
        $this->line("  Version:     {$module->getVersion()}");
        $this->line("  Alias:       {$module->getAlias()}");
        $this->line('  Description: '.($module->getDescription() ?: '-'));
        $this->line("  Path:        {$module->getPath()}");
        $this->line("  Namespace:   {$module->getNamespace()}");
        $this->line("  Priority:    {$module->getPriority()}");
        $this->line('  Status:      '.($module->isEnabled() ? '<fg=green>Enabled</>' : '<fg=yellow>Disabled</>'));
        $this->line('  Protected:   '.($module->isProtected() ? '<fg=blue>Yes</>' : 'No'));

        // Dependencies
        $this->newLine();
        $this->line('  <fg=cyan>Dependencies:</>');

        $requires = $module->getRequires();
        if (empty($requires)) {
            $this->line('    None');
        } else {
            foreach ($requires as $dep => $constraint) {
                $depModule = $modular->find($dep);
                $status = $depModule
                    ? ($depModule->isEnabled() ? '<fg=green>✓</>' : '<fg=yellow>○</>')
                    : '<fg=red>✗</>';
                $this->line("    {$status} {$dep} {$constraint}");
            }
        }

        // Dependents
        $this->newLine();
        $this->line('  <fg=cyan>Depended on by:</>');

        $dependents = $modular->getDependents($name);
        if ($dependents->isEmpty()) {
            $this->line('    None');
        } else {
            foreach ($dependents as $dependent) {
                $status = $dependent->isEnabled() ? '<fg=green>✓</>' : '<fg=yellow>○</>';
                $this->line("    {$status} {$dependent->getName()}");
            }
        }

        // Providers
        $this->newLine();
        $this->line('  <fg=cyan>Service Providers:</>');

        $providers = $module->getProviders();
        if (empty($providers)) {
            $this->line('    None');
        } else {
            foreach ($providers as $provider) {
                $exists = class_exists($provider);
                $status = $exists ? '<fg=green>✓</>' : '<fg=red>✗</>';
                $this->line("    {$status} {$provider}");
            }
        }

        // Validation
        $this->newLine();
        $result = $modular->validate($name);
        if ($result->isValid()) {
            $this->components->info('✓ Module is valid');
        } else {
            $this->components->error('✗ Validation errors:');
            foreach ($result->errors as $err) {
                $this->line("  • {$err}");
            }
        }

        return self::SUCCESS;
    }
}
