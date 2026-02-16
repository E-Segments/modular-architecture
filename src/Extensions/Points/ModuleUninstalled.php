<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Extensions\Points;

use Esegments\LaravelExtensions\Contracts\ExtensionPointContract;

/**
 * Extension point dispatched after a module is uninstalled/removed.
 *
 * Handlers can:
 * - Perform post-uninstall cleanup
 * - Remove related data
 * - Clear caches
 * - Send notifications
 * - Unregister services
 */
final class ModuleUninstalled implements ExtensionPointContract
{
    public function __construct(
        public readonly string $moduleName,
        public readonly string $path,
    ) {}
}
