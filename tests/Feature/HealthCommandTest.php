<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Tests\TestCase;

class HealthCommandTest extends TestCase
{
    public function test_health_command_shows_no_modules_message(): void
    {
        $this->artisan('modular:health')
            ->assertSuccessful();
    }

    public function test_health_check_for_nonexistent_module_fails(): void
    {
        $this->artisan('modular:health', ['module' => 'NonExistent'])
            ->assertFailed();
    }

    public function test_health_check_for_single_module(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:health', ['module' => 'Blog'])
            ->assertSuccessful();
    }

    public function test_health_check_for_all_modules(): void
    {
        $this->createTestModule('Blog');
        $this->createTestModule('Shop');

        $this->artisan('modular:health')
            ->assertSuccessful();
    }

    public function test_health_check_with_verbose_option(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:health', ['module' => 'Blog', '--detailed' => true])
            ->assertSuccessful();
    }

    public function test_health_check_detects_invalid_json_manifest(): void
    {
        $path = $this->createTestModule('InvalidJson');

        // Corrupt the module.json
        $this->app['files']->put($path.'/module.json', 'not valid json{');

        $this->artisan('modular:health', ['module' => 'InvalidJson'])
            ->assertFailed();
    }

    public function test_health_check_detects_missing_manifest_fields(): void
    {
        $path = $this->createTestModule('MissingFields');

        // Create manifest without required fields
        $this->app['files']->put($path.'/module.json', json_encode([
            'description' => 'Missing name and version',
        ]));

        $this->artisan('modular:health', ['module' => 'MissingFields'])
            ->assertSuccessful(); // Warnings don't cause failure
    }

    public function test_health_check_detects_missing_provider_class(): void
    {
        // Create module with invalid provider from the start
        $this->createTestModule('MissingProvider', [
            'providers' => ['Modules\\MissingProvider\\NonExistentServiceProvider'],
        ]);

        $this->artisan('modular:health', ['module' => 'MissingProvider'])
            ->assertFailed();
    }

    public function test_health_check_validates_config_files(): void
    {
        $path = $this->createTestModule('WithConfig');

        // Create valid config file
        $configPath = $path.'/config';
        $this->app['files']->ensureDirectoryExists($configPath);
        $this->app['files']->put($configPath.'/withconfig.php', '<?php return [\'key\' => \'value\'];');

        $this->artisan('modular:health', ['module' => 'WithConfig'])
            ->assertSuccessful();
    }

    public function test_health_check_detects_invalid_config_file(): void
    {
        $path = $this->createTestModule('BadConfig');

        // Create invalid config file that doesn't return array
        $configPath = $path.'/config';
        $this->app['files']->ensureDirectoryExists($configPath);
        $this->app['files']->put($configPath.'/badconfig.php', '<?php return "not an array";');

        $this->artisan('modular:health', ['module' => 'BadConfig'])
            ->assertFailed();
    }

    public function test_health_check_reports_migration_count(): void
    {
        $path = $this->createTestModule('WithMigrations');

        // Create migrations directory with files
        $migrationsPath = $path.'/database/migrations';
        $this->app['files']->ensureDirectoryExists($migrationsPath);
        $this->app['files']->put($migrationsPath.'/2024_01_01_000000_create_posts_table.php', '<?php // migration');
        $this->app['files']->put($migrationsPath.'/2024_01_02_000000_create_comments_table.php', '<?php // migration');

        $this->artisan('modular:health', ['module' => 'WithMigrations', '--detailed' => true])
            ->assertSuccessful();
    }

    public function test_health_check_validates_dependencies(): void
    {
        // Create module with missing dependency from the start
        $this->createTestModule('NeedsDep', [
            'requires' => ['NonExistentModule' => '^1.0'],
        ]);

        $this->artisan('modular:health', ['module' => 'NeedsDep'])
            ->assertFailed();
    }

    public function test_health_check_passes_when_dependencies_satisfied(): void
    {
        // Create the dependency module first
        $this->createTestModule('CoreModule');

        // Create dependent module with the dependency specified
        $this->createTestModule('DependentModule', [
            'requires' => ['CoreModule' => '^1.0'],
        ]);

        $this->artisan('modular:health', ['module' => 'DependentModule'])
            ->assertSuccessful();
    }
}
