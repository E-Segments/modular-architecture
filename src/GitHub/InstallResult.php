<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\GitHub;

use Esegments\ModularArchitecture\Module\Module;

class InstallResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?Module $module = null,
        public readonly ?string $source = null,
        public readonly ?string $version = null,
        public readonly ?string $path = null,
        public readonly ?string $error = null,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function failed(): bool
    {
        return ! $this->success;
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            error: $error,
        );
    }
}
