<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Tests\TestCase;

class PublishAssetsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up published assets
        $this->app['files']->deleteDirectory(public_path('modules'));

        parent::tearDown();
    }

    public function test_publish_assets_requires_module_name_or_all_flag(): void
    {
        $this->artisan('modular:publish-assets')
            ->assertFailed();
    }

    public function test_publish_assets_fails_for_nonexistent_module(): void
    {
        $this->artisan('modular:publish-assets', ['module' => 'NonExistent'])
            ->assertFailed();
    }

    public function test_publish_assets_for_single_module(): void
    {
        $path = $this->createTestModule('Blog');

        // Create assets directory with files
        $assetsPath = $path.'/resources/assets';
        $this->app['files']->ensureDirectoryExists($assetsPath.'/css');
        $this->app['files']->ensureDirectoryExists($assetsPath.'/js');
        $this->app['files']->put($assetsPath.'/css/app.css', 'body { color: red; }');
        $this->app['files']->put($assetsPath.'/js/app.js', 'console.log("Hello");');

        $this->artisan('modular:publish-assets', ['module' => 'Blog'])
            ->assertSuccessful();

        $this->assertFileExists(public_path('modules/blog/css/app.css'));
        $this->assertFileExists(public_path('modules/blog/js/app.js'));
    }

    public function test_publish_assets_for_all_modules(): void
    {
        $blogPath = $this->createTestModule('Blog');
        $shopPath = $this->createTestModule('Shop');

        // Create assets for Blog
        $blogAssetsPath = $blogPath.'/resources/assets';
        $this->app['files']->ensureDirectoryExists($blogAssetsPath);
        $this->app['files']->put($blogAssetsPath.'/blog.css', 'body {}');

        // Create assets for Shop
        $shopAssetsPath = $shopPath.'/resources/assets';
        $this->app['files']->ensureDirectoryExists($shopAssetsPath);
        $this->app['files']->put($shopAssetsPath.'/shop.css', 'div {}');

        $this->artisan('modular:publish-assets', ['--all' => true])
            ->assertSuccessful();

        $this->assertFileExists(public_path('modules/blog/blog.css'));
        $this->assertFileExists(public_path('modules/shop/shop.css'));
    }

    public function test_module_without_assets_is_skipped(): void
    {
        $this->createTestModule('NoAssets');

        $this->artisan('modular:publish-assets', ['module' => 'NoAssets'])
            ->assertSuccessful();
    }

    public function test_existing_assets_are_not_overwritten_without_force(): void
    {
        $path = $this->createTestModule('Blog');

        // Create assets
        $assetsPath = $path.'/resources/assets';
        $this->app['files']->ensureDirectoryExists($assetsPath);
        $this->app['files']->put($assetsPath.'/app.css', 'new content');

        // Create existing published assets
        $publishedPath = public_path('modules/blog');
        $this->app['files']->ensureDirectoryExists($publishedPath);
        $this->app['files']->put($publishedPath.'/app.css', 'old content');

        $this->artisan('modular:publish-assets', ['module' => 'Blog'])
            ->assertSuccessful();

        // Content should be unchanged without --force
        $this->assertEquals('old content', file_get_contents($publishedPath.'/app.css'));
    }

    public function test_existing_assets_are_overwritten_with_force(): void
    {
        $path = $this->createTestModule('Blog');

        // Create assets
        $assetsPath = $path.'/resources/assets';
        $this->app['files']->ensureDirectoryExists($assetsPath);
        $this->app['files']->put($assetsPath.'/app.css', 'new content');

        // Create existing published assets
        $publishedPath = public_path('modules/blog');
        $this->app['files']->ensureDirectoryExists($publishedPath);
        $this->app['files']->put($publishedPath.'/app.css', 'old content');

        $this->artisan('modular:publish-assets', ['module' => 'Blog', '--force' => true])
            ->assertSuccessful();

        // Content should be updated with --force
        $this->assertEquals('new content', file_get_contents($publishedPath.'/app.css'));
    }

    public function test_assets_are_published_to_correct_path(): void
    {
        $path = $this->createTestModule('Products');

        // Create nested assets
        $assetsPath = $path.'/resources/assets';
        $this->app['files']->ensureDirectoryExists($assetsPath.'/images/icons');
        $this->app['files']->put($assetsPath.'/images/icons/logo.png', 'fake image data');

        $this->artisan('modular:publish-assets', ['module' => 'Products'])
            ->assertSuccessful();

        $this->assertFileExists(public_path('modules/products/images/icons/logo.png'));
    }
}
