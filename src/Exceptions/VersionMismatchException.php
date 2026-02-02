<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

use Exception;

class VersionMismatchException extends Exception
{
    public function __construct(
        public readonly string $moduleName,
        public readonly string $dependencyName,
        public readonly string $requiredVersion,
        public readonly string $actualVersion,
        ?string $message = null,
    ) {
        parent::__construct(
            $message ?? "Module [{$moduleName}] requires [{$dependencyName}] version [{$requiredVersion}], but [{$actualVersion}] is installed."
        );
    }

    public static function forConstraint(
        string $module,
        string $dependency,
        string $constraint,
        string $installed,
    ): self {
        return new self(
            moduleName: $module,
            dependencyName: $dependency,
            requiredVersion: $constraint,
            actualVersion: $installed,
        );
    }
}
