<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

use Exception;

class DependencyException extends Exception
{
    public function __construct(
        public readonly string $moduleName,
        public readonly string $dependencyName,
        ?string $message = null,
    ) {
        parent::__construct($message ?? "Module [{$moduleName}] has unresolved dependency [{$dependencyName}].");
    }

    public static function missing(string $module, string $dependency): self
    {
        return new self(
            moduleName: $module,
            dependencyName: $dependency,
            message: "Module [{$module}] requires [{$dependency}] which is not installed."
        );
    }

    public static function disabled(string $module, string $dependency): self
    {
        return new self(
            moduleName: $module,
            dependencyName: $dependency,
            message: "Module [{$module}] requires [{$dependency}] which is disabled."
        );
    }
}
