<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

use Exception;

class ConflictException extends Exception
{
    public function __construct(
        public readonly string $moduleName,
        public readonly string $conflictingModule,
        ?string $message = null,
    ) {
        parent::__construct(
            $message ?? "Module [{$moduleName}] conflicts with [{$conflictingModule}]."
        );
    }

    public static function forModules(string $module, string $conflicting): self
    {
        return new self(
            moduleName: $module,
            conflictingModule: $conflicting,
        );
    }

    /**
     * @param  array<string>  $conflicts
     */
    public static function forMultipleConflicts(string $module, array $conflicts): self
    {
        return new self(
            moduleName: $module,
            conflictingModule: implode(', ', $conflicts),
            message: "Module [{$module}] conflicts with: " . implode(', ', $conflicts),
        );
    }
}
