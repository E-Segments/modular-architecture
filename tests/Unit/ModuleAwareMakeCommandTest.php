<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit;

use Esegments\ModularArchitecture\Commands\Concerns\ModuleAwareMakeCommand;
use Esegments\ModularArchitecture\Tests\TestCase;

class ModuleAwareMakeCommandTest extends TestCase
{
    protected object $command;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock command that uses the trait
        $this->command = new class
        {
            use ModuleAwareMakeCommand;

            public ?string $moduleOption = null;

            public function option(string $key): mixed
            {
                return $key === 'module' ? $this->moduleOption : null;
            }

            public function setModule(?string $module): void
            {
                $this->moduleOption = $module;
            }

            // Expose protected methods for testing
            public function exposeGetModuleDefaultNamespace(string $moduleName, string $type): string
            {
                return $this->getModuleDefaultNamespace($moduleName, $type);
            }

            public function exposeGetModuleFilePath(string $moduleName, string $type): string
            {
                return $this->getModuleFilePath($moduleName, $type);
            }

            public function exposeGetModuleNamespace(string $moduleName): string
            {
                return $this->getModuleNamespace($moduleName);
            }

            public function exposeHasModule(): bool
            {
                return $this->hasModule();
            }

            public function exposeGetModuleName(): ?string
            {
                return $this->getModuleName();
            }
        };
    }

    public function test_generates_correct_model_namespace(): void
    {
        $namespace = $this->command->exposeGetModuleDefaultNamespace('Blog', 'model');

        $this->assertEquals('Modules\\Blog\\Models', $namespace);
    }

    public function test_generates_correct_controller_namespace(): void
    {
        $namespace = $this->command->exposeGetModuleDefaultNamespace('Blog', 'controller');

        $this->assertEquals('Modules\\Blog\\Http\\Controllers', $namespace);
    }

    public function test_generates_correct_event_namespace(): void
    {
        $namespace = $this->command->exposeGetModuleDefaultNamespace('Blog', 'event');

        $this->assertEquals('Modules\\Blog\\Events', $namespace);
    }

    public function test_generates_correct_listener_namespace(): void
    {
        $namespace = $this->command->exposeGetModuleDefaultNamespace('Blog', 'listener');

        $this->assertEquals('Modules\\Blog\\Listeners', $namespace);
    }

    public function test_generates_correct_job_namespace(): void
    {
        $namespace = $this->command->exposeGetModuleDefaultNamespace('Blog', 'job');

        $this->assertEquals('Modules\\Blog\\Jobs', $namespace);
    }

    public function test_generates_correct_policy_namespace(): void
    {
        $namespace = $this->command->exposeGetModuleDefaultNamespace('Blog', 'policy');

        $this->assertEquals('Modules\\Blog\\Policies', $namespace);
    }

    public function test_generates_correct_request_namespace(): void
    {
        $namespace = $this->command->exposeGetModuleDefaultNamespace('Blog', 'request');

        $this->assertEquals('Modules\\Blog\\Http\\Requests', $namespace);
    }

    public function test_generates_correct_resource_namespace(): void
    {
        $namespace = $this->command->exposeGetModuleDefaultNamespace('Blog', 'resource');

        $this->assertEquals('Modules\\Blog\\Http\\Resources', $namespace);
    }

    public function test_generates_correct_module_path_for_model(): void
    {
        $this->createTestModule('Blog');

        $path = $this->command->exposeGetModuleFilePath('Blog', 'model');

        $this->assertStringEndsWith('Blog/app/Models', $path);
    }

    public function test_generates_correct_module_path_for_controller(): void
    {
        $this->createTestModule('Blog');

        $path = $this->command->exposeGetModuleFilePath('Blog', 'controller');

        $this->assertStringEndsWith('Blog/app/Http/Controllers', $path);
    }

    public function test_generates_correct_module_path_for_migration(): void
    {
        $this->createTestModule('Blog');

        $path = $this->command->exposeGetModuleFilePath('Blog', 'migration');

        $this->assertStringEndsWith('Blog/database/migrations', $path);
    }

    public function test_generates_correct_module_path_for_factory(): void
    {
        $this->createTestModule('Blog');

        $path = $this->command->exposeGetModuleFilePath('Blog', 'factory');

        $this->assertStringEndsWith('Blog/database/factories', $path);
    }

    public function test_generates_correct_module_path_for_seeder(): void
    {
        $this->createTestModule('Blog');

        $path = $this->command->exposeGetModuleFilePath('Blog', 'seeder');

        $this->assertStringEndsWith('Blog/database/seeders', $path);
    }

    public function test_generates_correct_module_path_for_test_feature(): void
    {
        $this->createTestModule('Blog');

        $path = $this->command->exposeGetModuleFilePath('Blog', 'test-feature');

        $this->assertStringEndsWith('Blog/tests/Feature', $path);
    }

    public function test_generates_correct_module_path_for_test_unit(): void
    {
        $this->createTestModule('Blog');

        $path = $this->command->exposeGetModuleFilePath('Blog', 'test-unit');

        $this->assertStringEndsWith('Blog/tests/Unit', $path);
    }

    public function test_has_module_returns_false_when_no_module(): void
    {
        $this->command->setModule(null);

        $this->assertFalse($this->command->exposeHasModule());
    }

    public function test_has_module_returns_true_when_module_set(): void
    {
        $this->command->setModule('Blog');

        $this->assertTrue($this->command->exposeHasModule());
    }

    public function test_get_module_name_returns_module_option(): void
    {
        $this->command->setModule('Products');

        $this->assertEquals('Products', $this->command->exposeGetModuleName());
    }

    public function test_get_module_namespace(): void
    {
        $namespace = $this->command->exposeGetModuleNamespace('Blog');

        $this->assertEquals('Modules\\Blog', $namespace);
    }
}
