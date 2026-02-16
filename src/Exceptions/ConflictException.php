<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

/**
 * Exception thrown when modules conflict with each other.
 */
class ConflictException extends ModuleException
{
    protected int $statusCode = 422;

    protected ?string $errorCode = 'MODULE_CONFLICT';

    public function __construct(
        public readonly string $moduleName,
        public readonly string $conflictingModule,
        ?string $message = null,
    ) {
        parent::__construct(
            message: $message ?? "Module [{$moduleName}] conflicts with [{$conflictingModule}].",
            context: [
                'module' => $moduleName,
                'conflicting_module' => $conflictingModule,
            ],
        );
        $this->setModuleName($moduleName);
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
            message: "Module [{$module}] conflicts with: ".implode(', ', $conflicts),
        );
    }
}
