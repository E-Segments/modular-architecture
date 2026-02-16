<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

/**
 * Exception thrown when a module dependency cannot be resolved.
 */
class DependencyException extends ModuleException
{
    protected int $statusCode = 422;

    protected ?string $errorCode = 'DEPENDENCY_ERROR';

    public function __construct(
        public readonly string $moduleName,
        public readonly string $dependencyName,
        ?string $message = null,
    ) {
        parent::__construct(
            message: $message ?? "Module [{$moduleName}] has unresolved dependency [{$dependencyName}].",
            context: [
                'module' => $moduleName,
                'dependency' => $dependencyName,
            ],
        );
        $this->setModuleName($moduleName);
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
