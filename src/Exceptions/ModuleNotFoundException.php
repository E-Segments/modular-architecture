<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

/**
 * Exception thrown when a module is not found.
 */
class ModuleNotFoundException extends ModuleException
{
    protected int $statusCode = 404;

    protected ?string $errorCode = 'MODULE_NOT_FOUND';

    public function __construct(
        public readonly string $moduleName,
        ?string $message = null,
    ) {
        parent::__construct(
            message: $message ?? "Module [{$moduleName}] not found.",
            context: ['module' => $moduleName],
        );
        $this->setModuleName($moduleName);
    }

    public static function forModule(string $name): self
    {
        return new self($name);
    }

    public static function forPath(string $path): self
    {
        return new self(
            moduleName: basename($path),
            message: "Module not found at path [{$path}]."
        );
    }
}
