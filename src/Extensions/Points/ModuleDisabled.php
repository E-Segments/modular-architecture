<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Extensions\Points;

use Esegments\LaravelExtensions\Contracts\ExtensionPointContract;
use Esegments\ModularArchitecture\Module\Module;

/**
 * Extension point dispatched after a module is disabled.
 *
 * Handlers can:
 * - Perform cleanup actions
 * - Clear caches
 * - Send notifications
 */
final class ModuleDisabled implements ExtensionPointContract
{
    public function __construct(
        public readonly Module $module,
        public readonly string $moduleName,
    ) {}
}
