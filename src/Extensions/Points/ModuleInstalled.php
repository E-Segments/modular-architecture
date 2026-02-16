<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Extensions\Points;

use Esegments\LaravelExtensions\Contracts\ExtensionPointContract;
use Esegments\ModularArchitecture\Module\Module;

/**
 * Extension point dispatched after a module is installed.
 *
 * Handlers can:
 * - Perform post-install actions
 * - Run migrations
 * - Clear caches
 * - Send notifications
 * - Register module services
 */
final class ModuleInstalled implements ExtensionPointContract
{
    public function __construct(
        public readonly Module $module,
        public readonly string $moduleName,
        public readonly string $source,
        public readonly string $version,
        public readonly string $path,
    ) {}
}
