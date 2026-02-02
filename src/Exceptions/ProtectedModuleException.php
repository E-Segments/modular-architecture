<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

use Exception;

class ProtectedModuleException extends Exception
{
    public function __construct(
        public readonly string $moduleName,
        ?string $message = null,
    ) {
        parent::__construct(
            $message ?? "Module [{$moduleName}] is protected and cannot be disabled or removed."
        );
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
