<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Bridges\BridgeManager;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BridgesInspectCommand extends Command
{
    protected $signature = 'modular:bridges:inspect
                            {bridge : The bridge name to inspect}
                            {--module= : Filter by specific module}
                            {--json : Output as JSON}';

    protected $description = 'Show detailed information about a specific bridge';

    public function handle(BridgeManager $manager): int
    {
        $bridgeName = $this->argument('bridge');
        $bridge = $manager->get($bridgeName);

        if (! $bridge) {
            // Try to find by partial match
            $bridge = $manager->all()->first(
                fn ($b) => Str::contains($b->name(), $bridgeName, ignoreCase: true)
            );

            if (! $bridge) {
                $this->components->error("Bridge [{$bridgeName}] not found.");
                $this->newLine();
                $this->line('Available bridges:');
                foreach ($manager->all() as $name => $b) {
                    $this->line("  - {$b->name()}");
                }

                return Command::FAILURE;
            }
        }

        if (! $bridge instanceof AbstractBridge) {
            $this->components->error('Bridge does not support inspection.');

            return Command::FAILURE;
        }

        $moduleFilter = $this->option('module');
        $status = $bridge->getStatus();
        $registered = $bridge->getRegistered();

        if ($this->option('json')) {
            $this->line(json_encode([
                'status' => $status,
                'modules' => $registered->toArray(),
            ], JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        // Bridge info
        $this->components->info("Bridge: {$bridge->name()}");
        $this->newLine();

        $this->components->twoColumnDetail(
            'Available',
            $status['available'] ? '<fg=green>Yes</>' : '<fg=red>No</>'
        );
        $this->components->twoColumnDetail(
            'Enabled',
            $status['enabled'] ? '<fg=green>Yes</>' : '<fg=gray>No</>'
        );
        $this->components->twoColumnDetail('Required class', $status['required_class']);
        $this->components->twoColumnDetail('Component path', $status['component_path']);
        $this->components->twoColumnDetail('Config key', "modular.bridges.{$status['config_key']}");
        $this->components->twoColumnDetail(
            'Loaded from cache',
            $status['from_cache'] ? '<fg=cyan>Yes</>' : 'No'
        );

        $this->newLine();

        // Registered modules
        if ($registered->isEmpty()) {
            $this->components->warn('No modules registered with this bridge.');

            return Command::SUCCESS;
        }

        $this->components->info('Registered Modules');
        $this->newLine();

        foreach ($registered as $moduleName => $data) {
            if ($moduleFilter && ! Str::contains($moduleName, $moduleFilter, ignoreCase: true)) {
                continue;
            }

            $this->line("  <comment>{$moduleName}</comment>");
            $this->displayModuleData($data, '    ');
            $this->newLine();
        }

        return Command::SUCCESS;
    }

    /**
     * Display module data recursively.
     */
    protected function displayModuleData(mixed $data, string $indent = ''): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    if ($this->isIndexedArray($value)) {
                        // Simple list
                        $count = count($value);
                        $this->line("{$indent}<fg=gray>{$key}:</> {$count} item(s)");
                        if ($count <= 5) {
                            foreach ($value as $item) {
                                if (is_string($item)) {
                                    $this->line("{$indent}  - {$this->shortenClass($item)}");
                                }
                            }
                        }
                    } else {
                        // Associative array
                        $this->line("{$indent}<fg=gray>{$key}:</>");
                        $this->displayModuleData($value, $indent.'  ');
                    }
                } else {
                    $displayValue = is_string($value)
                        ? $this->shortenClass($value)
                        : (string) $value;
                    $this->line("{$indent}<fg=gray>{$key}:</> {$displayValue}");
                }
            }
        }
    }

    /**
     * Check if array is indexed (not associative).
     */
    protected function isIndexedArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Shorten a class name for display.
     */
    protected function shortenClass(string $class): string
    {
        // If it's a path, return as-is
        if (str_contains($class, '/')) {
            return basename($class);
        }

        // Shorten class names
        if (str_starts_with($class, 'Modules\\')) {
            return Str::after($class, 'Modules\\');
        }

        return class_basename($class);
    }
}
