<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

/**
 * Exception thrown when trying to modify a protected module.
 */
class ProtectedModuleException extends ModuleException
{
    protected int $statusCode = 403;

    protected ?string $errorCode = 'PROTECTED_MODULE';

    public function __construct(
        public readonly string $moduleName,
        ?string $message = null,
    ) {
        parent::__construct(
            message: $message ?? "Module [{$moduleName}] is protected and cannot be disabled or removed.",
            context: ['module' => $moduleName],
        );
        $this->setModuleName($moduleName);
    }

    public static function cannotDisable(string $name): self
    {
        return new self(
            moduleName: $name,
            message: "Cannot disable protected module [{$name}].",
        );
    }

    public static function cannotRemove(string $name): self
    {
        return new self(
            moduleName: $name,
            message: "Cannot remove protected module [{$name}].",
        );
    }
}
