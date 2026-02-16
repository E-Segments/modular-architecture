<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit\Links;

use Esegments\ModularArchitecture\Links\LinkDefinition;
use Esegments\ModularArchitecture\Links\LinkRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the LinkRegistry singleton.
 */
class LinkRegistryTest extends TestCase
{
    private LinkRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new LinkRegistry;
    }

    public function test_it_defines_new_link_and_returns_definition(): void
    {
        $definition = $this->registry->define('ProductBrandLink');

        $this->assertInstanceOf(LinkDefinition::class, $definition);
        $this->assertSame('ProductBrandLink', $definition->getName());
    }

    public function test_it_registers_link_definition(): void
    {
        $definition = new LinkDefinition('TestLink');
        $definition->requires('ModuleA', 'ModuleB');

        $this->registry->register($definition);

        $this->assertTrue($this->registry->has('TestLink'));
        $this->assertSame($definition, $this->registry->get('TestLink'));
    }

    public function test_it_returns_null_for_unknown_link(): void
    {
        $this->assertNull($this->registry->get('NonExistentLink'));
        $this->assertFalse($this->registry->has('NonExistentLink'));
    }

    public function test_it_returns_all_registered_links(): void
    {
        $this->registry->define('LinkA');
        $this->registry->define('LinkB');
        $this->registry->define('LinkC');

        $all = $this->registry->all();

        $this->assertCount(3, $all);
        $this->assertTrue($all->has('LinkA'));
        $this->assertTrue($all->has('LinkB'));
        $this->assertTrue($all->has('LinkC'));
    }

    public function test_it_returns_summary_statistics(): void
    {
        $this->registry->define('LinkA');
        $this->registry->define('LinkB');

        $summary = $this->registry->summary();

        $this->assertArrayHasKey('total', $summary);
        $this->assertArrayHasKey('enabled', $summary);
        $this->assertArrayHasKey('disabled', $summary);
        $this->assertArrayHasKey('booted', $summary);
        $this->assertSame(2, $summary['total']);
    }

    public function test_it_returns_status_for_debugging(): void
    {
        // Note: Links with required modules need module checker
        // to check status. For unit tests, we test links without requirements.
        $this->registry->define('TestLink');

        $status = $this->registry->status();

        $this->assertArrayHasKey('TestLink', $status);
        $this->assertArrayHasKey('booted', $status['TestLink']);
        $this->assertArrayHasKey('requiredModules', $status['TestLink']);
    }

    public function test_it_clears_all_definitions(): void
    {
        $this->registry->define('LinkA');
        $this->registry->define('LinkB');

        $this->assertCount(2, $this->registry->all());

        $this->registry->clear();

        $this->assertCount(0, $this->registry->all());
        $this->assertFalse($this->registry->isBooted());
    }

    public function test_it_forgets_specific_definition(): void
    {
        $this->registry->define('LinkA');
        $this->registry->define('LinkB');

        $this->assertTrue($this->registry->forget('LinkA'));
        $this->assertFalse($this->registry->has('LinkA'));
        $this->assertTrue($this->registry->has('LinkB'));
    }

    public function test_forget_returns_false_for_unknown_link(): void
    {
        $this->assertFalse($this->registry->forget('NonExistentLink'));
    }

    public function test_it_tracks_booted_state(): void
    {
        $this->assertFalse($this->registry->isBooted());

        $this->registry->boot();

        $this->assertTrue($this->registry->isBooted());
    }

    public function test_boot_only_runs_once(): void
    {
        $callCount = 0;

        $this->registry->define('TestLink')->onBoot(function () use (&$callCount): void {
            $callCount++;
        });

        $this->registry->boot();
        $this->registry->boot();
        $this->registry->boot();

        $this->assertSame(1, $callCount);
    }

    public function test_links_without_required_modules_are_always_enabled(): void
    {
        $definition = $this->registry->define('TestLink');
        // No requires() call - should be enabled by default

        $this->assertTrue($this->registry->isLinkEnabled($definition));
        $this->assertTrue($this->registry->isLinkEnabled('TestLink'));
    }

    public function test_is_link_enabled_returns_false_for_unknown_link(): void
    {
        $this->assertFalse($this->registry->isLinkEnabled('NonExistentLink'));
    }

    public function test_it_can_set_module_checker(): void
    {
        $checkerCalled = false;

        $this->registry->setModuleChecker(function (array $modules) use (&$checkerCalled): bool {
            $checkerCalled = true;

            return count($modules) === 2;
        });

        $definition = $this->registry->define('TestLink')->requires('ModuleA', 'ModuleB');

        $this->assertTrue($this->registry->isLinkEnabled($definition));
        $this->assertTrue($checkerCalled);
    }

    public function test_module_checker_returns_false_disables_link(): void
    {
        $this->registry->setModuleChecker(fn (array $modules): bool => false);

        $definition = $this->registry->define('TestLink')->requires('ModuleA');

        $this->assertFalse($this->registry->isLinkEnabled($definition));
    }

    public function test_enabled_returns_only_enabled_links(): void
    {
        $this->registry->setModuleChecker(fn (array $modules): bool => in_array('EnabledModule', $modules));

        $this->registry->define('EnabledLink')->requires('EnabledModule');
        $this->registry->define('DisabledLink')->requires('DisabledModule');
        $this->registry->define('NoRequirements'); // No modules = enabled

        $enabled = $this->registry->enabled();

        $this->assertCount(2, $enabled);
        $this->assertTrue($enabled->has('EnabledLink'));
        $this->assertTrue($enabled->has('NoRequirements'));
        $this->assertFalse($enabled->has('DisabledLink'));
    }

    public function test_disabled_returns_only_disabled_links(): void
    {
        $this->registry->setModuleChecker(fn (array $modules): bool => in_array('EnabledModule', $modules));

        $this->registry->define('EnabledLink')->requires('EnabledModule');
        $this->registry->define('DisabledLink')->requires('DisabledModule');

        $disabled = $this->registry->disabled();

        $this->assertCount(1, $disabled);
        $this->assertTrue($disabled->has('DisabledLink'));
    }

    public function test_it_can_set_registries(): void
    {
        $registries = ['resourceConfig' => new \stdClass];

        $result = $this->registry->setRegistries($registries);

        $this->assertSame($this->registry, $result);
    }
}
