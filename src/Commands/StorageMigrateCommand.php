<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Storage\StorageManager;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class StorageMigrateCommand extends Command
{
    protected $signature = 'modular:storage:migrate
                            {--from= : Source storage driver}
                            {--to= : Target storage driver}';

    protected $description = 'Migrate module data between storage drivers';

    public function handle(StorageManager $storage): int
    {
        $from = $this->option('from') ?? select(
            label: 'Migrate from:',
            options: [
                'file' => 'Filesystem',
                'database' => 'Database',
            ],
        );

        $to = $this->option('to') ?? select(
            label: 'Migrate to:',
            options: array_filter([
                'file' => 'Filesystem',
                'database' => 'Database',
            ], fn ($key) => $key !== $from, ARRAY_FILTER_USE_KEY),
        );

        if ($from === $to) {
            $this->components->error('Source and target cannot be the same.');

            return Command::FAILURE;
        }

        $this->components->info("Migrating from [{$from}] to [{$to}]...");

        if (! confirm('This will copy all module states and metadata. Continue?')) {
            $this->components->warn('Migration cancelled.');

            return Command::SUCCESS;
        }

        try {
            $result = $storage->migrate($from, $to);

            $this->components->info('Migration complete!');
            $this->components->bulletList([
                "States migrated: {$result['states']}",
                "Metadata migrated: {$result['metadata']}",
            ]);

            $this->newLine();
            $this->components->warn("Update your .env file: MODULAR_STORAGE_DRIVER={$to}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error('Migration failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
