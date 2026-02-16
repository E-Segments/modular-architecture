<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit\Links;

use Esegments\ModularArchitecture\Links\LinkBootstrapper;
use Esegments\ModularArchitecture\Links\LinkRegistry;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the LinkBootstrapper service.
 */
class LinkBootstrapperTest extends TestCase
{
    private LinkRegistry $registry;

    private Application $app;

    private LinkBootstrapper $bootstrapper;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new LinkRegistry;
        $this->app = $this->createMock(Application::class);
        $this->app->method('hasDebugModeEnabled')->willReturn(false);

        // Create a temp directory for test modules
        $this->tempDir = sys_get_temp_dir().'/link_bootstrapper_test_'.uniqid();
        mkdir($this->tempDir, 0755, true);

        // Create bootstrapper with explicit modules path to avoid base_path() call
        $this->bootstrapper = new LinkBootstrapper($this->app, $this->registry, $this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        $this->recursiveDelete($this->tempDir);
        parent::tearDown();
    }

    private function recursiveDelete(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }

        rmdir($dir);
    }

    public function test_it_returns_modules_path(): void
    {
        $this->bootstrapper->setModulesPath('/custom/path');

        $this->assertSame('/custom/path', $this->bootstrapper->getModulesPath());
    }

    public function test_it_returns_empty_array_for_nonexistent_modules_path(): void
    {
        $this->bootstrapper->setModulesPath('/nonexistent/path');

        $files = $this->bootstrapper->discover();

        $this->assertSame([], $files);
        $this->assertTrue($this->bootstrapper->hasDiscovered());
    }

    public function test_it_discovers_link_files(): void
    {
        // Create a module with a Links directory
        $modulePath = $this->tempDir.'/TestModule/app/Links';
        mkdir($modulePath, 0755, true);
        file_put_contents($modulePath.'/TestLink.php', '<?php // Test link');

        $this->bootstrapper->setModulesPath($this->tempDir);

        $files = $this->bootstrapper->discover();

        $this->assertCount(1, $files);
        $this->assertStringContainsString('TestLink.php', $files[0]);
    }

    public function test_it_caches_discovery_results(): void
    {
        $this->bootstrapper->setModulesPath($this->tempDir);

        $files1 = $this->bootstrapper->discover();
        $files2 = $this->bootstrapper->discover();

        $this->assertSame($files1, $files2);
    }

    public function test_it_discovers_for_specific_module(): void
    {
        // Create module with links
        $modulePath = $this->tempDir.'/Products/app/Links';
        mkdir($modulePath, 0755, true);
        file_put_contents($modulePath.'/ProductBrandLink.php', '<?php // Link');
        file_put_contents($modulePath.'/ProductCategoryLink.php', '<?php // Link');

        $this->bootstrapper->setModulesPath($this->tempDir);

        $files = $this->bootstrapper->discoverForModule('Products');

        $this->assertCount(2, $files);
    }

    public function test_it_returns_empty_for_module_without_links(): void
    {
        $modulePath = $this->tempDir.'/EmptyModule';
        mkdir($modulePath, 0755, true);

        $this->bootstrapper->setModulesPath($this->tempDir);

        $files = $this->bootstrapper->discoverForModule('EmptyModule');

        $this->assertSame([], $files);
    }

    public function test_it_identifies_modules_with_links(): void
    {
        // Create modules
        mkdir($this->tempDir.'/Products/app/Links', 0755, true);
        file_put_contents($this->tempDir.'/Products/app/Links/TestLink.php', '<?php');

        mkdir($this->tempDir.'/Brands/app/Links', 0755, true);
        file_put_contents($this->tempDir.'/Brands/app/Links/TestLink.php', '<?php');

        mkdir($this->tempDir.'/Orders', 0755, true); // No Links directory

        $this->bootstrapper->setModulesPath($this->tempDir);

        $modules = $this->bootstrapper->getModulesWithLinks();

        $this->assertContains('Products', $modules);
        $this->assertContains('Brands', $modules);
        $this->assertNotContains('Orders', $modules);
    }

    public function test_it_provides_statistics(): void
    {
        $this->bootstrapper->setModulesPath($this->tempDir);
        $this->bootstrapper->discover();

        $stats = $this->bootstrapper->statistics();

        $this->assertArrayHasKey('modules_path', $stats);
        $this->assertArrayHasKey('discovered_files', $stats);
        $this->assertArrayHasKey('registry_summary', $stats);
    }

    public function test_it_resets_discovery_state(): void
    {
        // Create module with link
        $modulePath = $this->tempDir.'/TestModule/app/Links';
        mkdir($modulePath, 0755, true);
        file_put_contents($modulePath.'/TestLink.php', '<?php');

        $this->bootstrapper->setModulesPath($this->tempDir);
        $this->bootstrapper->discover();

        $this->assertTrue($this->bootstrapper->hasDiscovered());
        $this->assertNotEmpty($this->bootstrapper->getDiscoveredFiles());

        $this->bootstrapper->reset();

        $this->assertFalse($this->bootstrapper->hasDiscovered());
        $this->assertEmpty($this->bootstrapper->getDiscoveredFiles());
    }
}
