<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Module;

use Esegments\ModularArchitecture\Exceptions\InvalidManifestException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
final class ModuleManifest implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string>  $providers
     * @param  array<string, string>  $requires
     * @param  array<string, string>  $conflicts
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $alias = '',
        public readonly string $description = '',
        public readonly int $priority = 0,
        public readonly array $providers = [],
        public readonly array $requires = [],
        public readonly array $conflicts = [],
        public readonly array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $path = ''): self
    {
        $missing = [];
        if (! isset($data['name']) || ! is_string($data['name'])) {
            $missing[] = 'name';
        }
        if (! isset($data['version']) || ! is_string($data['version'])) {
            $missing[] = 'version';
        }

        if (! empty($missing)) {
            throw InvalidManifestException::missingFields($path, $missing);
        }

        return new self(
            name: $data['name'],
            version: $data['version'],
            alias: Arr::get($data, 'alias', strtolower($data['name'])),
            description: Arr::get($data, 'description', ''),
            priority: (int) Arr::get($data, 'priority', 0),
            providers: Arr::get($data, 'providers', []),
            requires: Arr::get($data, 'requires', []),
            conflicts: Arr::get($data, 'conflicts', []),
            extra: Arr::except($data, [
                'name',
                'version',
                'alias',
                'description',
                'priority',
                'providers',
                'requires',
                'conflicts',
            ]),
        );
    }

    public static function fromJsonFile(string $path): self
    {
        if (! file_exists($path)) {
            throw InvalidManifestException::notFound($path);
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw InvalidManifestException::invalidJson($path, 'Could not read file');
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw InvalidManifestException::invalidJson($path, json_last_error_msg());
        }

        return self::fromArray($data, $path);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'alias' => $this->alias,
            'version' => $this->version,
            'description' => $this->description,
            'priority' => $this->priority,
            'providers' => $this->providers,
            'requires' => $this->requires,
            'conflicts' => $this->conflicts,
            ...$this->extra,
        ], fn ($value) => ! empty($value) || $value === 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(int $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->toArray(), $options) ?: '{}';
    }

    public function getExtra(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->extra, $key, $default);
    }

    public function hasRequirement(string $name): bool
    {
        return isset($this->requires[$name]);
    }

    public function getRequiredVersion(string $name): ?string
    {
        return $this->requires[$name] ?? null;
    }

    public function hasConflict(string $name): bool
    {
        return isset($this->conflicts[$name]);
    }
}
