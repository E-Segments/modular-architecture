<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Module;

use Esegments\Core\Concerns\Makeable;
use Esegments\Core\Contracts\Arrayable;
use Esegments\ModularArchitecture\Contracts\ModuleContract;
use Esegments\ModularArchitecture\Exceptions\InvalidManifestException;
use Esegments\ModularArchitecture\Exceptions\ProtectedModuleException;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
class Module implements Arrayable, JsonSerializable, ModuleContract
{
    use Makeable;

    protected bool $enabled = true;

    protected bool $protected = false;

    protected ?Collection $dependencies = null;

    protected ?Collection $dependents = null;

    public function __construct(
        protected readonly ModuleManifest $manifest,
        protected readonly string $path,
        ?bool $enabled = null,
        ?bool $protected = null,
    ) {
        if ($enabled !== null) {
            $this->enabled = $enabled;
        }
        if ($protected !== null) {
            $this->protected = $protected;
        }
    }

    public static function fromPath(string $path): self
    {
        $manifestPath = rtrim($path, '/').'/module.json';

        if (! file_exists($manifestPath)) {
            throw InvalidManifestException::notFound($manifestPath);
        }

        $manifest = ModuleManifest::fromJsonFile($manifestPath);

        return new self($manifest, $path);
    }

    public function getName(): string
    {
        return $this->manifest->name;
    }

    public function getAlias(): string
    {
        return $this->manifest->alias;
    }

    public function getVersion(): string
    {
        return $this->manifest->version;
    }

    public function getDescription(): string
    {
        return $this->manifest->description;
    }

    public function getPriority(): int
    {
        return $this->manifest->priority;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getProviders(): array
    {
        return $this->manifest->providers;
    }

    public function getRequires(): array
    {
        return $this->manifest->requires;
    }

    public function getConflicts(): array
    {
        return $this->manifest->conflicts;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isProtected(): bool
    {
        return $this->protected;
    }

    public function setProtected(bool $protected = true): self
    {
        $this->protected = $protected;

        return $this;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        if ($this->protected) {
            throw ProtectedModuleException::cannotDisable($this->getName());
        }

        $this->enabled = false;
    }

    public function getManifest(): array
    {
        return $this->manifest->toArray();
    }

    public function getManifestObject(): ModuleManifest
    {
        return $this->manifest;
    }

    public function getDependencies(): Collection
    {
        if ($this->dependencies === null) {
            $this->dependencies = collect();
        }

        return $this->dependencies;
    }

    public function setDependencies(Collection $dependencies): self
    {
        $this->dependencies = $dependencies;

        return $this;
    }

    public function getDependents(): Collection
    {
        if ($this->dependents === null) {
            $this->dependents = collect();
        }

        return $this->dependents;
    }

    public function setDependents(Collection $dependents): self
    {
        $this->dependents = $dependents;

        return $this;
    }

    /**
     * Build a path within the module directory.
     */
    protected function buildPath(string $segment, ?string $subPath = null): string
    {
        $base = $this->path.'/'.$segment;

        return $subPath ? $base.'/'.ltrim($subPath, '/') : $base;
    }

    public function getAppPath(?string $subPath = null): string
    {
        return $this->buildPath('app', $subPath);
    }

    public function getConfigPath(?string $subPath = null): string
    {
        return $this->buildPath('config', $subPath);
    }

    public function getDatabasePath(?string $subPath = null): string
    {
        return $this->buildPath('database', $subPath);
    }

    public function getResourcesPath(?string $subPath = null): string
    {
        return $this->buildPath('resources', $subPath);
    }

    public function getRoutesPath(?string $subPath = null): string
    {
        return $this->buildPath('routes', $subPath);
    }

    public function getTestsPath(?string $subPath = null): string
    {
        return $this->buildPath('tests', $subPath);
    }

    public function getNamespace(): string
    {
        return "Modules\\{$this->getName()}";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'alias' => $this->getAlias(),
            'version' => $this->getVersion(),
            'description' => $this->getDescription(),
            'priority' => $this->getPriority(),
            'path' => $this->getPath(),
            'providers' => $this->getProviders(),
            'requires' => $this->getRequires(),
            'conflicts' => $this->getConflicts(),
            'enabled' => $this->isEnabled(),
            'protected' => $this->isProtected(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
