<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Extensions\Points;

use Esegments\LaravelExtensions\Concerns\InterruptibleTrait;
use Esegments\LaravelExtensions\Contracts\InterruptibleContract;

/**
 * Extension point dispatched before a module is installed.
 *
 * Handlers can:
 * - Add validation errors
 * - Return false to prevent installation
 * - Perform pre-install checks
 */
final class BeforeModuleInstall implements InterruptibleContract
{
    use InterruptibleTrait;

    /** @var array<string> */
    public array $errors = [];

    public function __construct(
        public readonly string $source,
        public readonly ?string $version = null,
    ) {}

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }
}
