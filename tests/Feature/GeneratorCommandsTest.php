<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Tests\TestCase;

class GeneratorCommandsTest extends TestCase
{
    public function test_make_service_command_creates_service(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:make-service', [
            'module' => 'Blog',
            'name' => 'Post',
        ])
            ->assertSuccessful();

        $this->assertFileExists(
            $this->getTestModulesPath().'/Blog/app/Services/PostService.php'
        );
    }

    public function test_make_service_command_strips_suffix(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:make-service', [
            'module' => 'Blog',
            'name' => 'PostService',
        ])
            ->assertSuccessful();

        $this->assertFileExists(
            $this->getTestModulesPath().'/Blog/app/Services/PostService.php'
        );
    }

    public function test_make_service_command_fails_for_nonexistent_module(): void
    {
        $this->artisan('modular:make-service', [
            'module' => 'NonExistent',
            'name' => 'Post',
        ])
            ->assertFailed();
    }

    public function test_make_repository_command_creates_repository(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:make-repository', [
            'module' => 'Blog',
            'name' => 'Post',
        ])
            ->assertSuccessful();

        $this->assertFileExists(
            $this->getTestModulesPath().'/Blog/app/Repositories/PostRepository.php'
        );
    }

    public function test_make_dto_command_creates_dto(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:make-dto', [
            'module' => 'Blog',
            'name' => 'CreatePost',
        ])
            ->assertSuccessful();

        $this->assertFileExists(
            $this->getTestModulesPath().'/Blog/app/Data/CreatePostData.php'
        );
    }

    public function test_make_action_command_creates_action(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:make-action', [
            'module' => 'Blog',
            'name' => 'CreatePost',
        ])
            ->assertSuccessful();

        $this->assertFileExists(
            $this->getTestModulesPath().'/Blog/app/Actions/CreatePostAction.php'
        );
    }

    public function test_make_observer_command_creates_observer(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:make-observer', [
            'module' => 'Blog',
            'name' => 'Post',
        ])
            ->assertSuccessful();

        $this->assertFileExists(
            $this->getTestModulesPath().'/Blog/app/Observers/PostObserver.php'
        );
    }

    public function test_make_enum_command_creates_enum(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:make-enum', [
            'module' => 'Blog',
            'name' => 'PostStatus',
        ])
            ->assertSuccessful();

        $this->assertFileExists(
            $this->getTestModulesPath().'/Blog/app/Enums/PostStatus.php'
        );
    }

    public function test_make_service_command_does_not_overwrite_without_force(): void
    {
        $path = $this->createTestModule('Blog');

        // Create services directory and file
        $servicesPath = $path.'/app/Services';
        $this->app['files']->ensureDirectoryExists($servicesPath);
        $this->app['files']->put($servicesPath.'/PostService.php', '<?php // existing');

        $this->artisan('modular:make-service', [
            'module' => 'Blog',
            'name' => 'Post',
        ])
            ->assertFailed();
    }

    public function test_make_service_command_overwrites_with_force(): void
    {
        $path = $this->createTestModule('Blog');

        // Create services directory and file
        $servicesPath = $path.'/app/Services';
        $this->app['files']->ensureDirectoryExists($servicesPath);
        $this->app['files']->put($servicesPath.'/PostService.php', '<?php // existing');

        $this->artisan('modular:make-service', [
            'module' => 'Blog',
            'name' => 'Post',
            '--force' => true,
        ])
            ->assertSuccessful();

        $content = file_get_contents($servicesPath.'/PostService.php');
        $this->assertStringContainsString('class PostService', $content);
    }

    public function test_generated_service_has_correct_namespace(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:make-service', [
            'module' => 'Blog',
            'name' => 'Post',
        ]);

        $content = file_get_contents(
            $this->getTestModulesPath().'/Blog/app/Services/PostService.php'
        );

        $this->assertStringContainsString('namespace Modules\\Blog\\Services;', $content);
        $this->assertStringContainsString('class PostService', $content);
    }

    public function test_generated_dto_extends_spatie_data(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:make-dto', [
            'module' => 'Blog',
            'name' => 'CreatePost',
        ]);

        $content = file_get_contents(
            $this->getTestModulesPath().'/Blog/app/Data/CreatePostData.php'
        );

        $this->assertStringContainsString('use Spatie\\LaravelData\\Data;', $content);
        $this->assertStringContainsString('extends Data', $content);
    }

    public function test_generated_enum_has_get_label_method(): void
    {
        $this->createTestModule('Blog');

        $this->artisan('modular:make-enum', [
            'module' => 'Blog',
            'name' => 'PostStatus',
        ]);

        $content = file_get_contents(
            $this->getTestModulesPath().'/Blog/app/Enums/PostStatus.php'
        );

        $this->assertStringContainsString('enum PostStatus: string', $content);
        $this->assertStringContainsString('public function getLabel(): string', $content);
    }
}
