<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Bridges\Blade\BladeBridge;
use Esegments\ModularArchitecture\Bridges\BridgeManager;
use Esegments\ModularArchitecture\Bridges\Contracts\BridgeContract;
use Esegments\ModularArchitecture\Bridges\Event\EventBridge;
use Esegments\ModularArchitecture\Bridges\Filament\FilamentBridge;
use Esegments\ModularArchitecture\Bridges\Livewire\LivewireBridge;
use Esegments\ModularArchitecture\Bridges\Route\RouteBridge;
use Esegments\ModularArchitecture\Bridges\Schedule\ScheduleBridge;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class BridgeManagerTest extends TestCase
{
    protected BridgeManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->app->make(BridgeManager::class);
    }

    public function test_bridge_manager_is_registered(): void
    {
        $this->assertInstanceOf(BridgeManager::class, $this->manager);
    }

    public function test_all_bridges_are_registered(): void
    {
        $bridges = $this->manager->all();

        $this->assertInstanceOf(LivewireBridge::class, $bridges->get('livewire'));
        $this->assertInstanceOf(FilamentBridge::class, $bridges->get('filament'));
        $this->assertInstanceOf(RouteBridge::class, $bridges->get('routes'));
        $this->assertInstanceOf(EventBridge::class, $bridges->get('events'));
        $this->assertInstanceOf(BladeBridge::class, $bridges->get('blade'));
        $this->assertInstanceOf(ScheduleBridge::class, $bridges->get('schedule'));
    }

    public function test_bridges_are_disabled_by_default(): void
    {
        $enabled = $this->manager->enabled();

        $this->assertTrue($enabled->isEmpty());
    }

    public function test_enabled_bridges_are_filtered(): void
    {
        // Enable blade bridge (doesn't require external package)
        Config::set('modular.bridges.blade.enabled', true);

        $enabled = $this->manager->enabled();

        $this->assertCount(1, $enabled);
        $this->assertInstanceOf(BladeBridge::class, $enabled->first());
    }

    public function test_bridge_availability_checks_required_class(): void
    {
        $bladeBridge = $this->manager->get('blade');

        // Blade is always available in Laravel
        $this->assertTrue($bladeBridge->isAvailable());
    }

    public function test_livewire_bridge_checks_availability(): void
    {
        $livewireBridge = $this->manager->get('livewire');

        // Livewire availability depends on whether package is installed
        $expectedAvailable = class_exists('Livewire\\Livewire');
        $this->assertEquals($expectedAvailable, $livewireBridge->isAvailable());
    }

    public function test_filament_bridge_checks_availability(): void
    {
        $filamentBridge = $this->manager->get('filament');

        // Filament availability depends on whether package is installed
        $expectedAvailable = class_exists('Filament\\Panel');
        $this->assertEquals($expectedAvailable, $filamentBridge->isAvailable());
    }

    public function test_bridge_status_returns_all_bridges(): void
    {
        $status = $this->manager->status();

        $this->assertArrayHasKey('livewire', $status);
        $this->assertArrayHasKey('filament', $status);
        $this->assertArrayHasKey('route', $status);
        $this->assertArrayHasKey('event', $status);
        $this->assertArrayHasKey('blade', $status);
        $this->assertArrayHasKey('schedule', $status);

        foreach ($status as $bridgeStatus) {
            $this->assertArrayHasKey('available', $bridgeStatus);
            $this->assertArrayHasKey('enabled', $bridgeStatus);
            $this->assertArrayHasKey('components', $bridgeStatus);
        }
    }

    public function test_bridge_manager_boots_only_once(): void
    {
        $this->assertFalse($this->manager->isBooted());

        $this->manager->boot();
        $this->assertTrue($this->manager->isBooted());

        // Should not throw or change state
        $this->manager->boot();
        $this->assertTrue($this->manager->isBooted());
    }

    public function test_custom_bridge_can_be_registered(): void
    {
        $customBridge = new class($this->app) extends AbstractBridge
        {
            public function name(): string
            {
                return 'custom';
            }

            protected function configKey(): string
            {
                return 'custom';
            }

            protected function requiredClass(): string
            {
                return 'stdClass';
            }

            protected function componentPath(): string
            {
                return 'app/Custom';
            }

            public function registerModule(Module $module): void {}

            public function boot(): void {}
        };

        $this->manager->register('custom', $customBridge);

        $this->assertInstanceOf(BridgeContract::class, $this->manager->get('custom'));
        $this->assertEquals('custom', $this->manager->get('custom')->name());
    }
}
