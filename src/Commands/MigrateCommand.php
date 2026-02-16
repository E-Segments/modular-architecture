<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\LaravelExtensions\Facades\Extensions;
use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleMigrate;
use Esegments\ModularArchitecture\Extensions\Points\ModuleMigrated;
use Esegments\ModularArchitecture\Modular;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MigrateCommand extends Command
{
    protected $signature = 'modular:migrate
                            {module? : The name of the module to migrate}
                            {--all : Migrate all enabled modules}
                            {--fresh : Drop all tables and re-run all migrations}
                            {--seed : Seed the database after migration}
                            {--force : Force the operation in production}';

    protected $description = 'Run migrations for a module or all modules';

    public function __construct(
        protected readonly Filesystem $files,
    ) {
        parent::__construct();
    }

    public function handle(Modular $modular): int
    {
        $moduleName = $this->argument('module');
        $migrateAll = $this->option('all');

        if (! $moduleName && ! $migrateAll) {
            $this->components->error('Please specify a module name or use --all to migrate all modules.');

            return Command::FAILURE;
        }

        if ($migrateAll) {
            return $this->migrateAllModules($modular);
        }

        return $this->migrateModule($modular, $moduleName);
    }

    protected function migrateAllModules(Modular $modular): int
    {
        $modules = $modular->enabled();
        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        $this->components->info('Running migrations for all enabled modules...');
        $this->newLine();

        foreach ($modules as $module) {
            $result = $this->runModuleMigration($modular, $module->getName());

            if ($result === true) {
                $migrated++;
            } elseif ($result === null) {
                $skipped++;
            } else {
                $failed++;
            }
        }

        $this->newLine();
        $this->components->info("Migrated: {$migrated} | Skipped: {$skipped} | Failed: {$failed}");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function migrateModule(Modular $modular, string $name): int
    {
        if (! $modular->exists($name)) {
            $this->components->error("Module [{$name}] not found.");

            return Command::FAILURE;
        }

        $result = $this->runModuleMigration($modular, $name);

        if ($result === null) {
            $this->components->warn("Module [{$name}] has no migrations.");

            return Command::SUCCESS;
        }

        if ($result === false) {
            return Command::FAILURE;
        }

        $this->newLine();
        $this->components->info("Migrations completed for module [{$name}].");

        return Command::SUCCESS;
    }

    /**
     * Run migrations for a single module.
     *
     * @return bool|null true if migrated, false if failed, null if no migrations
     */
    protected function runModuleMigration(Modular $modular, string $name): ?bool
    {
        $module = $modular->findOrFail($name);
        $migrationsPath = $module->getDatabasePath('migrations');

        if (! $this->files->isDirectory($migrationsPath)) {
            return null;
        }

        $migrations = $this->files->glob($migrationsPath.'/*.php');
        if (empty($migrations)) {
            return null;
        }

        $fresh = $this->option('fresh');
        $seed = $this->option('seed');

        // Dispatch BeforeModuleMigrate extension point (interruptible)
        $beforeMigrate = new BeforeModuleMigrate($module, $name, $fresh, $seed);
        $canProceed = Extensions::dispatchInterruptible($beforeMigrate);

        if (! $canProceed) {
            $handler = $beforeMigrate->getInterruptedBy();
            $this->components->twoColumnDetail(
                $name,
                '<fg=yellow>Cancelled by extension</>'.($handler ? " ({$handler})" : '')
            );

            return false;
        }

        if ($beforeMigrate->hasErrors()) {
            $this->components->twoColumnDetail(
                $name,
                '<fg=red>Blocked</>'
            );
            foreach ($beforeMigrate->errors as $error) {
                $this->line("  - {$error}");
            }

            return false;
        }

        // Build migration command
        $command = $fresh ? 'migrate:fresh' : 'migrate';
        $params = [
            '--path' => str_replace(base_path().'/', '', $migrationsPath),
            '--realpath' => true,
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        if ($seed) {
            $params['--seed'] = true;
        }

        try {
            $this->call($command, $params);

            // Dispatch ModuleMigrated extension point (graceful - errors shouldn't block completed migration)
            Extensions::gracefully()->dispatch(new ModuleMigrated(
                module: $module,
                moduleName: $name,
                migrationCount: count($migrations),
            ));

            $this->components->twoColumnDetail(
                $name,
                '<fg=green>'.count($migrations).' migration(s) run</>'
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
}
