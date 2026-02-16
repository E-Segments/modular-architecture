<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

/**
 * Exception thrown when a module version constraint is not satisfied.
 */
class VersionMismatchException extends ModuleException
{
    protected int $statusCode = 422;

    protected ?string $errorCode = 'VERSION_MISMATCH';

    public function __construct(
        public readonly string $moduleName,
        public readonly string $dependencyName,
        public readonly string $requiredVersion,
        public readonly string $actualVersion,
        ?string $message = null,
    ) {
        parent::__construct(
            message: $message ?? "Module [{$moduleName}] requires [{$dependencyName}] version [{$requiredVersion}], but [{$actualVersion}] is installed.",
            context: [
                'module' => $moduleName,
                'dependency' => $dependencyName,
                'required_version' => $requiredVersion,
                'actual_version' => $actualVersion,
            ],
        );
        $this->setModuleName($moduleName);
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
