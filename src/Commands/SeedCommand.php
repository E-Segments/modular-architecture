<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\LaravelExtensions\Facades\Extensions;
use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleSeed;
use Esegments\ModularArchitecture\Extensions\Points\ModuleSeeded;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class SeedCommand extends Command
{
    protected $signature = 'modular:seed
                            {module? : The name of the module to seed}
                            {--all : Seed all enabled modules}
                            {--class= : Specific seeder class to run}
                            {--force : Force the operation in production}';

    protected $description = 'Run seeders for a module or all modules';

    public function __construct(
        protected readonly Filesystem $files,
    ) {
        parent::__construct();
    }

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $seedAll = $this->option('all');

        if (! $moduleName && ! $seedAll) {
            $this->components->error('Please specify a module name or use --all to seed all modules.');

            return Command::FAILURE;
        }

        if ($seedAll) {
            return $this->seedAllModules($modular);
        }

        return $this->seedModule($modular, $moduleName);
    }

    protected function seedAllModules(Modular $modular): int
    {
        $modules = $modular->enabled();
        $seeded = 0;
        $skipped = 0;
        $failed = 0;

        $this->components->info('Running seeders for all enabled modules...');
        $this->newLine();

        foreach ($modules as $module) {
            $result = $this->runModuleSeeder($modular, $module->getName());

            if ($result === true) {
                $seeded++;
            } elseif ($result === null) {
                $skipped++;
            } else {
                $failed++;
            }
        }

        $this->newLine();
        $this->components->info("Seeded: {$seeded} | Skipped: {$skipped} | Failed: {$failed}");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function seedModule(Modular $modular, string $name): int
    {
        if (! $modular->exists($name)) {
            $this->components->error("Module [{$name}] not found.");

            return Command::FAILURE;
        }

        $result = $this->runModuleSeeder($modular, $name);

        if ($result === null) {
            $this->components->warn("Module [{$name}] has no seeders.");

            return Command::SUCCESS;
        }

        if ($result === false) {
            return Command::FAILURE;
        }

        $this->newLine();
        $this->components->info("Seeding completed for module [{$name}].");

        return Command::SUCCESS;
    }

    /**
     * Run seeders for a single module.
     *
     * @return bool|null true if seeded, false if failed, null if no seeders
     */
    protected function runModuleSeeder(Modular $modular, string $name): ?bool
    {
        $module = $modular->findOrFail($name);
        $seedersPath = $module->getDatabasePath('seeders');

        // Check if seeders directory exists
        if (! $this->files->isDirectory($seedersPath)) {
            return null;
        }

        $seeders = $this->files->glob($seedersPath.'/*.php');
        if (empty($seeders)) {
            return null;
        }

        $specificClass = $this->option('class');

        // Try to find the main DatabaseSeeder or specified class
        $seederClass = $specificClass ?? $this->findMainSeeder($module->getName(), $seedersPath);

        if (! $seederClass) {
            return null;
        }

        // Dispatch BeforeModuleSeed extension point (interruptible)
        $beforeSeed = new BeforeModuleSeed($module, $name, $seederClass);
        $canProceed = Extensions::dispatchInterruptible($beforeSeed);

        if (! $canProceed) {
            $handler = $beforeSeed->getInterruptedBy();
            $this->components->twoColumnDetail(
                $name,
                '<fg=yellow>Cancelled by extension</>'.($handler ? " ({$handler})" : '')
            );

            return false;
        }

        if ($beforeSeed->hasErrors()) {
            $this->components->twoColumnDetail(
                $name,
                '<fg=red>Blocked</>'
            );
            foreach ($beforeSeed->errors as $error) {
                $this->line("  - {$error}");
            }

            return false;
        }

        // Build seed command params
        $params = [
            '--class' => $seederClass,
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        try {
            $this->call('db:seed', $params);

            // Dispatch ModuleSeeded extension point (graceful - errors shouldn't block completed seeding)
            Extensions::gracefully()->dispatch(new ModuleSeeded(
                module: $module,
                moduleName: $name,
                seederClass: $seederClass,
            ));

            $this->components->twoColumnDetail(
                $name,
                '<fg=green>Seeded successfully</>'
            );

            return true;
        } catch (\Exception $e) {
            $this->components->twoColumnDetail(
                $name,
                '<fg=red>Failed: '.$e->getMessage().'</>'
            );

            return false;
        }
    }

    /**
     * Find the main seeder class for a module.
     */
    protected function findMainSeeder(string $moduleName, string $seedersPath): ?string
    {
        // Common naming conventions for main seeders
        $candidates = [
            "Modules\\{$moduleName}\\Database\\Seeders\\DatabaseSeeder",
            "Modules\\{$moduleName}\\Database\\Seeders\\{$moduleName}DatabaseSeeder",
            "Modules\\{$moduleName}\\Database\\Seeders\\{$moduleName}Seeder",
        ];

        foreach ($candidates as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        // Try to find any seeder file
        $seeders = $this->files->glob($seedersPath.'/*Seeder.php');
        if (! empty($seeders)) {
            $filename = basename($seeders[0], '.php');

            return "Modules\\{$moduleName}\\Database\\Seeders\\{$filename}";
        }

        return null;
    }
}
