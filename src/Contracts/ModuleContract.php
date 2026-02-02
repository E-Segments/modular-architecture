<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Contracts;

use Illuminate\Support\Collection;

interface ModuleContract
{
    public function getName(): string;

    public function getAlias(): string;

    public function getVersion(): string;

    public function getDescription(): string;

    public function getPriority(): int;

    public function getPath(): string;

    public function getProviders(): array;

    public function getRequires(): array;

    public function isEnabled(): bool;

    public function isProtected(): bool;

    public function enable(): void;

    public function disable(): void;

    public function getManifest(): array;

    public function getDependencies(): Collection;

    public function getDependents(): Collection;
}
