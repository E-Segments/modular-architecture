<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Tests\TestCase;

class CommandsTest extends TestCase
{
    public function test_list_command_shows_no_modules_message(): void
    {
        $this->artisan('modular:list')
            ->assertSuccessful()
            ->expectsOutput('No modules found.');
    }

    public function test_list_command_shows_modules(): void
    {
        $this->createTestModule('Blog');
        $this->createTestModule('Products');

        $this->artisan('modular:list')
            ->assertSuccessful()
            ->expectsOutputToContain('Blog')
            ->expectsOutputToContain('Products');
    }

    public function test_list_command_filters_enabled(): void
    {
        $this->createTestModule('Blog');
        $this->createTestModule('Products');

        // Disable one module
        $this->artisan('modular:disable', ['name' => 'Products']);

        $this->artisan('modular:list', ['--enabled' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Blog');
    }

    public function test_status_command_shows_overview(): void
    {
        $this->createTestModule('Blog');
        $this->createTestModule('Products');

        $this->artisan('modular:status')
            ->assertSuccessful()
            ->expectsOutputToContain('Module Overview')
            ->expectsOutputToContain('Total Modules');
    }

    public function test_status_command_shows_module_details(): void
    {
        $this->createTestModule('Blog', [
            'description' => 'Blog module for posts',
            'version' => '2.0.0',
        ]);

        $this->artisan('modular:status', ['name' => 'Blog'])
            ->assertSuccessful()
            ->expectsOutputToContain('Blog')
            ->expectsOutputToContain('2.0.0')
            ->expectsOutputToContain('Blog module for posts');
    }

    public function test_enable_command(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:disable', ['name' => 'Blog']);
        $this->artisan('modular:enable', ['name' => 'Blog'])
            ->assertSuccessful()
            ->expectsOutputToContain('enabled successfully');
    }

    public function test_disable_command(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:disable', ['name' => 'Blog'])
            ->assertSuccessful()
            ->expectsOutputToContain('disabled successfully');
    }

    public function test_disable_fails_for_nonexistent_module(): void
    {
        $this->artisan('modular:disable', ['name' => 'NonExistent'])
            ->assertFailed()
            ->expectsOutputToContain('not found');
    }

    public function test_validate_command_passes_for_valid_modules(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:validate')
            ->assertSuccessful()
            ->expectsOutputToContain('validated successfully');
    }

    public function test_validate_command_shows_errors(): void
    {
        $this->createTestModule('Blog', [
            'requires' => ['NonExistent' => '^1.0'],
        ]);

        $this->artisan('modular:validate')
            ->assertFailed()
            ->expectsOutputToContain('NonExistent');
    }

    public function test_dependents_command(): void
    {
        $this->createTestModule('Core');
        $this->createTestModule('Blog', ['requires' => ['Core' => '^1.0']]);

        $this->artisan('modular:dependents', ['name' => 'Core'])
            ->assertSuccessful()
            ->expectsOutputToContain('Blog');
    }

    public function test_graph_command_text_format(): void
    {
        $this->createTestModule('Core');
        $this->createTestModule('Blog', ['requires' => ['Core' => '^1.0']]);

        $this->artisan('modular:graph')
            ->assertSuccessful()
            ->expectsOutputToContain('Core')
            ->expectsOutputToContain('Blog');
    }

    public function test_graph_command_mermaid_format(): void
    {
        $this->createTestModule('Core');
        $this->createTestModule('Blog', ['requires' => ['Core' => '^1.0']]);

        $this->artisan('modular:graph', ['--format' => 'mermaid'])
            ->assertSuccessful()
            ->expectsOutputToContain('graph LR')
            ->expectsOutputToContain('Blog --> Core');
    }

    public function test_graph_command_dot_format(): void
    {
        $this->createTestModule('Core');
        $this->createTestModule('Blog', ['requires' => ['Core' => '^1.0']]);

        $this->artisan('modular:graph', ['--format' => 'dot'])
            ->assertSuccessful()
            ->expectsOutputToContain('digraph modules')
            ->expectsOutputToContain('"Blog" -> "Core"');
    }

    public function test_cache_command(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:cache')
            ->assertSuccessful()
            ->expectsOutputToContain('cache built successfully');
    }

    public function test_cache_clear_command(): void
    {
        $this->artisan('modular:cache:clear')
            ->assertSuccessful()
            ->expectsOutputToContain('cleared successfully');
    }

    public function test_optimize_command(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:optimize')
            ->assertSuccessful()
            ->expectsOutputToContain('Optimization complete');
    }
}
