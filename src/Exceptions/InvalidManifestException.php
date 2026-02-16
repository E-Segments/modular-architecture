<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

/**
 * Exception thrown when a module manifest is invalid.
 */
class InvalidManifestException extends ModuleException
{
    protected int $statusCode = 422;

    protected ?string $errorCode = 'INVALID_MANIFEST';

    /**
     * @param  array<string>  $errors
     */
    public function __construct(
        public readonly string $modulePath,
        public readonly array $errors = [],
        ?string $message = null,
    ) {
        $errorList = ! empty($errors) ? ': '.implode(', ', $errors) : '';
        parent::__construct(
            message: $message ?? "Invalid module.json at [{$modulePath}]{$errorList}",
            context: [
                'path' => $modulePath,
                'errors' => $errors,
            ],
        );
    }

    public static function notFound(string $path): self
    {
        return new self(
            modulePath: $path,
            message: "module.json not found at [{$path}]",
        );
    }

    public static function invalidJson(string $path, string $error): self
    {
        return new self(
            modulePath: $path,
            errors: [$error],
            message: "Invalid JSON in module.json at [{$path}]: {$error}",
        );
    }

    /**
     * @param  array<string>  $missing
     */
    public static function missingFields(string $path, array $missing): self
    {
        return new self(
            modulePath: $path,
            errors: $missing,
            message: "Missing required fields in module.json at [{$path}]: ".implode(', ', $missing),
        );
    }
}
