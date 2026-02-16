<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Health\ModuleHealthChecker;
use Esegments\ModularArchitecture\Modular;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Console\Command;

class HealthCommand extends Command
{
    protected $signature = 'modular:health
                            {module? : The name of a specific module to check}
                            {--detailed : Show detailed check results}';

    protected $description = 'Check the health of modules';

    public function handle(Modular $modular, ModuleHealthChecker $checker): int
    {
        $moduleName = $this->argument('module');

        if ($moduleName) {
            return $this->checkSingleModule($modular, $checker, $moduleName);
        }

        return $this->checkAllModules($modular, $checker);
    }

    protected function checkSingleModule(Modular $modular, ModuleHealthChecker $checker, string $name): int
    {
        if (! $modular->exists($name)) {
            $this->components->error("Module [{$name}] does not exist.");

            return Command::FAILURE;
        }

        $module = $modular->find($name);
        $results = $checker->check($module);
        $overall = $checker->getOverallStatus($results);

        $this->displayModuleHealth($module, $results, $overall);

        return $overall === 'error' ? Command::FAILURE : Command::SUCCESS;
    }

    protected function checkAllModules(Modular $modular, ModuleHealthChecker $checker): int
    {
        $modules = $modular->all();

        if ($modules->isEmpty()) {
            $this->components->warn('No modules found.');

            return Command::SUCCESS;
        }

        $this->components->info('Module Health Check');
        $this->newLine();

        $hasErrors = false;
        $summary = ['ok' => 0, 'warning' => 0, 'error' => 0];

        foreach ($modules as $module) {
            $results = $checker->check($module);
            $overall = $checker->getOverallStatus($results);

            $summary[$overall]++;

            if ($overall === 'error') {
                $hasErrors = true;
            }

            $this->displayModuleHealth($module, $results, $overall);
        }

        $this->newLine();
        $this->displaySummary($summary, $modules->count());

        return $hasErrors ? Command::FAILURE : Command::SUCCESS;
    }

    protected function displayModuleHealth(Module $module, array $results, string $overall): void
    {
        $statusIcon = match ($overall) {
            'ok' => '<fg=green>✓</>',
            'warning' => '<fg=yellow>⚠</>',
            'error' => '<fg=red>✗</>',
            default => '?',
        };

        $statusColor = match ($overall) {
            'ok' => 'green',
            'warning' => 'yellow',
            'error' => 'red',
            default => 'gray',
        };

        $this->components->twoColumnDetail(
            "{$statusIcon} {$module->getName()} <fg=gray>v{$module->getVersion()}</>",
            "<fg={$statusColor}>".$this->getStatusLabel($overall).'</>'
        );

        if ($this->option('detailed') || $overall !== 'ok') {
            foreach ($results as $check => $result) {
                if ($result['status'] !== 'ok' && $result['status'] !== 'info') {
                    $checkIcon = $result['status'] === 'error' ? '<fg=red>✗</>' : '<fg=yellow>!</>';
                    $this->line("    {$checkIcon} {$check}: {$result['message']}");

                    if (isset($result['details'])) {
                        foreach ($result['details'] as $detail) {
                            $this->line("      - {$detail}");
                        }
                    }
                } elseif ($this->option('detailed')) {
                    $checkIcon = $result['status'] === 'ok' ? '<fg=green>✓</>' : '<fg=gray>-</>';
                    $this->line("    {$checkIcon} {$check}: {$result['message']}");
                }
            }
        }
    }

    protected function displaySummary(array $summary, int $total): void
    {
        $this->components->info('Summary');

        $this->components->bulletList([
            "Total modules: {$total}",
            "<fg=green>Healthy: {$summary['ok']}</>",
            "<fg=yellow>Warnings: {$summary['warning']}</>",
            "<fg=red>Errors: {$summary['error']}</>",
        ]);
    }

    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'ok' => 'Healthy',
            'warning' => 'Warnings',
            'error' => 'Errors',
            default => 'Unknown',
        };
    }
}
