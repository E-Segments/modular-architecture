<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Modular;
use Esegments\ModularArchitecture\Tests\TestCase;

class ConfigMergeTest extends TestCase
{
    public function test_module_config_is_merged_with_alias_name(): void
    {
        $path = $this->createTestModule('Products');

        // Create config directory and file
        $configPath = $path.'/config';
        $this->app['files']->ensureDirectoryExists($configPath);
        $this->app['files']->put($configPath.'/products.php', '<?php return [
            "low_stock_threshold" => 5,
            "enable_reviews" => true,
        ];');

        // Manually trigger config merge
        $this->mergeModuleConfigs();

        $this->assertEquals(5, config('products.low_stock_threshold'));
        $this->assertTrue(config('products.enable_reviews'));
    }

    public function test_module_config_is_merged_from_default_config_file(): void
    {
        $path = $this->createTestModule('Blog');

        // Create config directory with default config.php
        $configPath = $path.'/config';
        $this->app['files']->ensureDirectoryExists($configPath);
        $this->app['files']->put($configPath.'/config.php', '<?php return [
            "posts_per_page" => 10,
            "allow_comments" => true,
        ];');

        // Manually trigger config merge
        $this->mergeModuleConfigs();

        $this->assertEquals(10, config('blog.posts_per_page'));
        $this->assertTrue(config('blog.allow_comments'));
    }

    public function test_alias_config_takes_precedence_over_default(): void
    {
        $path = $this->createTestModule('Shop');

        // Create both config files
        $configPath = $path.'/config';
        $this->app['files']->ensureDirectoryExists($configPath);

        $this->app['files']->put($configPath.'/config.php', '<?php return [
            "currency" => "EUR",
        ];');

        $this->app['files']->put($configPath.'/shop.php', '<?php return [
            "currency" => "USD",
        ];');

        // Manually trigger config merge
        $this->mergeModuleConfigs();

        // The alias file (shop.php) should take precedence
        $this->assertEquals('USD', config('shop.currency'));
    }

    public function test_disabled_module_config_is_not_merged(): void
    {
        $path = $this->createTestModule('DisabledModule');

        // Create config
        $configPath = $path.'/config';
        $this->app['files']->ensureDirectoryExists($configPath);
        $this->app['files']->put($configPath.'/disabledmodule.php', '<?php return [
            "should_not_exist" => true,
        ];');

        // Disable the module
        $this->app->make(Modular::class)->disable('DisabledModule');
        \Esegments\ModularArchitecture\Facades\Modular::refresh();

        // Manually trigger config merge
        $this->mergeModuleConfigs();

        $this->assertNull(config('disabledmodule.should_not_exist'));
    }

    public function test_module_without_config_does_not_fail(): void
    {
        $this->createTestModule('NoConfig');

        // Manually trigger config merge - should not throw any errors
        $this->mergeModuleConfigs();

        $this->assertNull(config('noconfig'));
        $this->assertTrue(true); // Just verify we got here without errors
    }

    /**
     * Helper method to manually trigger config merge.
     */
    protected function mergeModuleConfigs(): void
    {
        $modular = $this->app->make(Modular::class);
        $modules = $modular->enabled();

        foreach ($modules as $module) {
            $alias = $module->getAlias();
            $configPath = $module->getConfigPath();

            // Try config/{alias}.php first
            $aliasConfigPath = $configPath.'/'.$alias.'.php';
            if (file_exists($aliasConfigPath)) {
                $this->app['config']->set($alias, require $aliasConfigPath);

                continue;
            }

            // Fall back to config/config.php
            $defaultConfigPath = $configPath.'/config.php';
            if (file_exists($defaultConfigPath)) {
                $this->app['config']->set($alias, require $defaultConfigPath);
            }
        }
    }
}
