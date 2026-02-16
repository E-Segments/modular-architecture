<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Extensions\Points;

use Esegments\LaravelExtensions\Contracts\ExtensionPointContract;
use Esegments\ModularArchitecture\Module\Module;

/**
 * Extension point dispatched after a module's seeders are run.
 *
 * Handlers can:
 * - Perform post-seed actions
 * - Clear caches
 * - Rebuild indexes
 * - Send notifications
 */
final class ModuleSeeded implements ExtensionPointContract
{
    public function __construct(
        public readonly Module $module,
        public readonly string $moduleName,
        public readonly ?string $seederClass = null,
    ) {}
}
