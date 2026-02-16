<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Tests\TestCase;

class SeedCommandTest extends TestCase
{
    public function test_seed_command_requires_module_name_or_all_flag(): void
    {
        $this->artisan('modular:seed')
            ->assertFailed();
    }

    public function test_seed_command_fails_for_nonexistent_module(): void
    {
        $this->artisan('modular:seed', ['module' => 'NonExistent'])
            ->assertFailed();
    }

    public function test_seed_command_handles_module_without_seeders(): void
    {
        $this->createTestModule('NoSeeders');

        $this->artisan('modular:seed', ['module' => 'NoSeeders'])
            ->assertSuccessful();
    }

    public function test_seed_all_modules_with_no_seeders(): void
    {
        $this->createTestModule('Blog');
        $this->createTestModule('Shop');

        // Both modules have no seeders - should succeed with skips
        $this->artisan('modular:seed', ['--all' => true])
            ->assertSuccessful();
    }
}
