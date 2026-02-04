<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Extensions\Points;

use Esegments\LaravelExtensions\Concerns\InterruptibleTrait;
use Esegments\LaravelExtensions\Contracts\InterruptibleContract;
use Esegments\ModularArchitecture\Module\Module;

/**
 * Extension point dispatched before a module is enabled.
 *
 * Handlers can:
 * - Add validation errors
 * - Return false to prevent enabling
 * - Perform pre-enable checks
 */
final class BeforeModuleEnable implements InterruptibleContract
{
    use InterruptibleTrait;

    /** @var array<string> */
    public array $errors = [];

    public function __construct(
        public readonly Module $module,
        public readonly string $moduleName,
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
