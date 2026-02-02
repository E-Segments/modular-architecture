<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

use Exception;

class ModuleNotFoundException extends Exception
{
    public function __construct(
        public readonly string $moduleName,
        ?string $message = null,
    ) {
        parent::__construct($message ?? "Module [{$moduleName}] not found.");
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
