<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Extensions\Points;

use Esegments\LaravelExtensions\Contracts\ExtensionPointContract;
use Esegments\ModularArchitecture\Module\Module;

/**
 * Extension point dispatched after a module's migrations are run.
 *
 * Handlers can:
 * - Perform post-migration actions
 * - Seed data
 * - Clear caches
 * - Update schema caches
 * - Send notifications
 */
final class ModuleMigrated implements ExtensionPointContract
{
    public function __construct(
        public readonly Module $module,
        public readonly string $moduleName,
        public readonly int $migrationCount,
    ) {}
}
