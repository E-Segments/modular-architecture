<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;

class ValidateCommand extends Command
{
    protected $signature = 'modular:validate
                            {name? : Specific module to validate}
                            {--strict : Fail on any warning or error}';

    protected $description = 'Validate module dependencies and configuration';

    public function handle(Modular $modular): int
    {
        $name = $this->argument('name');
        $strict = $this->option('strict');

        if ($name) {
            return $this->validateModule($modular, $name, $strict);
        }

        return $this->validateAll($modular, $strict);
    }

    protected function validateModule(Modular $modular, string $name, bool $strict): int
    {
        if (! $modular->exists($name)) {
            $this->components->error("Module [{$name}] not found.");

            return Command::FAILURE;
        }

        $result = $modular->validate($name);

        if ($result->isValid()) {
            $this->components->info("✓ Module [{$name}] is valid.");
        } else {
            $this->components->error("✗ Module [{$name}] has errors:");
            foreach ($result->errors as $error) {
                $this->components->bulletList([$error]);
            }
        }

        if ($result->hasWarnings()) {
            $this->components->warn('Warnings:');
            foreach ($result->warnings as $warning) {
                $this->components->bulletList([$warning]);
            }
        }

        if ($strict && (! $result->isValid() || $result->hasWarnings())) {
            return Command::FAILURE;
        }

        return $result->isValid() ? Command::SUCCESS : Command::FAILURE;
    }

    protected function validateAll(Modular $modular, bool $strict): int
    {
        $this->components->info('Validating all modules...');
        $this->newLine();

        $hasErrors = false;
        $hasWarnings = false;

        foreach ($modular->all() as $module) {
            $result = $modular->validate($module->getName());

            $status = match (true) {
                ! $result->isValid() => '<fg=red>✗</>',
                $result->hasWarnings() => '<fg=yellow>⚠</>',
                default => '<fg=green>✓</>',
            };

            $version = $module->getVersion();
            $deps = array_keys($module->getRequires());
            $depsStr = empty($deps) ? '-' : implode(', ', $deps);

            $this->line("{$status} {$module->getName()} v{$version}");

            if (! $result->isValid()) {
                $hasErrors = true;
                foreach ($result->errors as $error) {
                    $this->line("   <fg=red>→ {$error}</>");
                }
            }

            if ($result->hasWarnings()) {
                $hasWarnings = true;
                foreach ($result->warnings as $warning) {
                    $this->line("   <fg=yellow>→ {$warning}</>");
                }
            }
        }

        $this->newLine();

        if ($hasErrors) {
            $this->components->error('Validation failed - errors found.');

            return Command::FAILURE;
        }

        if ($hasWarnings && $strict) {
            $this->components->warn('Validation passed with warnings (strict mode).');

            return Command::FAILURE;
        }

        if ($hasWarnings) {
            $this->components->warn('Validation passed with warnings.');

            return Command::SUCCESS;
        }

        $this->components->info('All modules validated successfully.');

        return Command::SUCCESS;
    }
}
