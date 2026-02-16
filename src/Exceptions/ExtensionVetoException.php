<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

/**
 * Exception thrown when an extension handler vetoes a module operation.
 */
class ExtensionVetoException extends ModuleException
{
    protected int $statusCode = 422;

    protected ?string $errorCode = 'EXTENSION_VETO';

    /**
     * @param  array<string>  $errors
     */
    public function __construct(
        public readonly string $moduleName,
        public readonly string $operation,
        public readonly array $errors = [],
        public readonly ?string $interruptedBy = null,
        string $message = '',
    ) {
        parent::__construct(
            message: $message,
            context: [
                'module' => $moduleName,
                'operation' => $operation,
                'errors' => $errors,
                'interrupted_by' => $interruptedBy,
            ],
        );
        $this->setModuleName($moduleName);
    }

    /**
     * Create exception for vetoed enable operation.
     *
     * @param  array<string>  $errors
     */
    public static function forEnable(
        string $moduleName,
        array $errors = [],
        ?string $interruptedBy = null,
    ): self {
        $errorMsg = empty($errors) ? '' : ' Errors: '.implode(', ', $errors);
        $vetoMsg = $interruptedBy ? " Vetoed by: {$interruptedBy}" : '';

        return new self(
            moduleName: $moduleName,
            operation: 'enable',
            errors: $errors,
            interruptedBy: $interruptedBy,
            message: "Cannot enable module [{$moduleName}] - operation was vetoed by extension handler.{$vetoMsg}{$errorMsg}",
        );
    }

    /**
     * Create exception for vetoed disable operation.
     *
     * @param  array<string>  $errors
     */
    public static function forDisable(
        string $moduleName,
        array $errors = [],
        ?string $interruptedBy = null,
    ): self {
        $errorMsg = empty($errors) ? '' : ' Errors: '.implode(', ', $errors);
        $vetoMsg = $interruptedBy ? " Vetoed by: {$interruptedBy}" : '';

        return new self(
            moduleName: $moduleName,
            operation: 'disable',
            errors: $errors,
            interruptedBy: $interruptedBy,
            message: "Cannot disable module [{$moduleName}] - operation was vetoed by extension handler.{$vetoMsg}{$errorMsg}",
        );
    }
}
