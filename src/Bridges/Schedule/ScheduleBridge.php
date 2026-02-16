<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Bridges\Schedule;

use Esegments\ModularArchitecture\Bridges\AbstractBridge;
use Esegments\ModularArchitecture\Module\Module;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Schedule Bridge.
 *
 * Auto-discovers and registers scheduled tasks from enabled modules.
 *
 * Discovery:
 * - app/Console/Kernel.php -> Traditional kernel schedule()
 * - app/Console/Schedule.php -> Standalone schedule class
 * - routes/console.php -> Schedule definitions in console routes
 *
 * Standalone Schedule class example:
 *
 * ```php
 * class Schedule
 * {
 *     public function __invoke(\Illuminate\Console\Scheduling\Schedule $schedule): void
 *     {
 *         $schedule->command('products:sync')->hourly();
 *     }
 * }
 * ```
 */
class ScheduleBridge extends AbstractBridge
{
    /**
     * Discovered schedule classes.
     *
     * @var array<string>
     */
    protected array $scheduleClasses = [];

    public function name(): string
    {
        return 'schedule';
    }

    protected function configKey(): string
    {
        return 'schedule';
    }

    protected function requiredClass(): string
    {
        return 'Illuminate\\Console\\Scheduling\\Schedule';
    }

    protected function componentPath(): string
    {
        return 'app/Console';
    }

    public function registerModule(Module $module): void
    {
        $consolePath = $module->getPath().'/'.$this->componentPath();

        if (! is_dir($consolePath)) {
            return;
        }

        $discovered = $this->discoverScheduleClass($module, $consolePath);

        if (! $discovered) {
            return;
        }

        $this->registered->put($module->getName(), [
            'schedule_class' => $discovered,
        ]);

        $this->scheduleClasses[] = $discovered;

        $this->log("Discovered {$module->getName()} schedule", [
            'class' => $discovered,
        ]);
    }

    public function boot(): void
    {
        if (empty($this->scheduleClasses)) {
            return;
        }

        // Register callback with the app to modify the schedule
        // This is called when the schedule is built
        $this->app->booted(function () {
            $this->registerSchedules();
        });
    }

    /**
     * Register all discovered schedules.
     */
    protected function registerSchedules(): void
    {
        if (! $this->app->bound(Schedule::class)) {
            return;
        }

        $schedule = $this->app->make(Schedule::class);

        foreach ($this->scheduleClasses as $class) {
            $this->invokeScheduleClass($schedule, $class);
        }

        $this->log('Registered scheduled tasks', [
            'classes' => count($this->scheduleClasses),
        ]);
    }

    /**
     * Invoke a schedule class.
     */
    protected function invokeScheduleClass(Schedule $schedule, string $class): void
    {
        if (! class_exists($class)) {
            return;
        }

        $instance = $this->app->make($class);

        // Support __invoke() method
        if (method_exists($instance, '__invoke')) {
            $instance($schedule);

            return;
        }

        // Support schedule() method
        if (method_exists($instance, 'schedule')) {
            $instance->schedule($schedule);

            return;
        }

        // Support register() method
        if (method_exists($instance, 'register')) {
            $instance->register($schedule);
        }
    }

    /**
     * Discover the schedule class for a module.
     */
    protected function discoverScheduleClass(Module $module, string $consolePath): ?string
    {
        $namespace = "Modules\\{$module->getName()}\\Console";

        // Check for standalone Schedule class first (preferred)
        $scheduleClass = $namespace.'\\Schedule';
        if (class_exists($scheduleClass)) {
            return $scheduleClass;
        }

        // Check for ScheduleServiceProvider
        $providerClass = $namespace.'\\ScheduleServiceProvider';
        if (class_exists($providerClass)) {
            return $providerClass;
        }

        // Check for Kernel with schedule method
        $kernelClass = $namespace.'\\Kernel';
        if (class_exists($kernelClass) && method_exists($kernelClass, 'schedule')) {
            return $kernelClass;
        }

        return null;
    }

    /**
     * Get all discovered schedule classes.
     *
     * @return array<string>
     */
    public function getScheduleClasses(): array
    {
        return $this->scheduleClasses;
    }
}
