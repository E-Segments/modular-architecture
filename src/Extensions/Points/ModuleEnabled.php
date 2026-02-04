<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Extensions\Points;

use Esegments\LaravelExtensions\Contracts\ExtensionPointContract;
use Esegments\ModularArchitecture\Module\Module;

/**
 * Extension point dispatched after a module is enabled.
 *
 * Handlers can:
 * - Perform post-enable actions
 * - Trigger migrations
 * - Clear caches
 * - Send notifications
 */
final class ModuleEnabled implements ExtensionPointContract
{
    public function __construct(
        public readonly Module $module,
        public readonly string $moduleName,
    ) {}
}
