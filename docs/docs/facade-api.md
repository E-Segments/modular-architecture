---
layout: docs
title: Facade API Reference
description: Complete Modular and Links facade methods
---

## Modular Facade

```php
use Esegments\ModularArchitecture\Facades\Modular;
```

### Module Retrieval

```php
// Get all modules
Modular::all(): ModuleCollection

// Get enabled modules (in load order)
Modular::enabled(): ModuleCollection

// Get disabled modules
Modular::disabled(): ModuleCollection

// Find module by name
Modular::find(string $name): ?Module

// Find or throw exception
Modular::findOrFail(string $name): Module
```

### Module State

```php
// Check if module exists
Modular::exists(string $name): bool

// Check if enabled
Modular::isEnabled(string $name): bool

// Check if protected
Modular::isProtected(string $name): bool

// Enable module
Modular::enable(string $name): Module

// Disable module
Modular::disable(string $name): Module
```

### Module Creation

```php
// Create new module
Modular::create(string $name, array $options = []): Module

// Options:
// - version: string
// - description: string
// - model: bool
// - controller: bool
// - views: bool
// - routes: bool
// - config: bool
// - tests: bool
```

### Module Removal

```php
// Delete module
Modular::delete(string $name): bool
```

### Dependencies

```php
// Get modules that depend on this module
Modular::getDependents(string $name): ModuleCollection

// Get this module's dependencies
Modular::getDependencies(string $name): ModuleCollection
```

### Validation

```php
// Validate single module
Modular::validate(string $name): ValidationResult

// Validate all modules
Modular::validateAll(): ValidationResult

// Validate per module
Modular::validateAllPerModule(): array<string, ValidationResult>
```

### Cache

```php
// Build cache
Modular::cache(): void

// Clear cache
Modular::clearCache(): void
```

### Statistics

```php
// Get module statistics
Modular::stats(): array
// Returns: ['total' => 10, 'enabled' => 8, 'disabled' => 2, 'protected' => 1]

// Count all modules
Modular::count(): int

// Count enabled modules
Modular::enabledCount(): int
```

### Internal Access

```php
// Get registry instance
Modular::getRegistry(): ModuleRegistry

// Get resolver instance
Modular::getResolver(): DependencyResolver

// Get discovery instance
Modular::getDiscovery(): ModuleDiscovery

// Get state manager
Modular::getStateManager(): ModuleStateManager
```

---

## Links Facade

```php
use Esegments\ModularArchitecture\Facades\Links;
```

### Link Definition

```php
// Define new link
Links::define(string $name): LinkDefinition

// Example:
Links::define('TaxProductLink')
    ->requires('Tax', 'Products')
    ->belongsTo(Product::class, 'taxClass', TaxClass::class, 'tax_class_id')
    ->hasMany(TaxClass::class, 'products', Product::class, 'tax_class_id');
```

### Link Registration

```php
// Register existing link definition
Links::register(LinkDefinition $definition): void

// Check if link exists
Links::has(string $name): bool

// Get specific link
Links::get(string $name): ?LinkDefinition
```

### Link Retrieval

```php
// Get all links
Links::all(): Collection

// Get enabled links (required modules exist)
Links::enabled(): Collection

// Get disabled links (missing required modules)
Links::disabled(): Collection
```

### Link Status

```php
// Check if link is enabled
Links::isEnabled(string $name): bool

// Get link status summary
Links::status(): array

// Get detailed summary
Links::summary(): array
```

### Link Lifecycle

```php
// Boot all enabled links
Links::boot(): void
```

---

## ModuleCollection Methods

```php
// Filter enabled
$modules->enabled(): ModuleCollection

// Filter disabled
$modules->disabled(): ModuleCollection

// Filter protected
$modules->protected(): ModuleCollection

// Sort by priority
$modules->sortByPriority(): ModuleCollection

// Sort by name
$modules->sortByName(): ModuleCollection

// Find by name
$modules->findByName(string $name): ?Module

// Find by alias
$modules->findByAlias(string $alias): ?Module

// Get modules depending on another
$modules->dependingOn(string $name): ModuleCollection

// Get dependencies of a module
$modules->dependenciesOf(string $name): ModuleCollection

// Get all service providers
$modules->allProviders(): array
```

---

## Module Object

```php
$module = Modular::find('Blog');

// Identity
$module->getName(): string
$module->getAlias(): string
$module->getVersion(): string
$module->getDescription(): string

// State
$module->isEnabled(): bool
$module->isProtected(): bool
$module->getPriority(): int

// Paths
$module->getPath(): string
$module->getPath('app/Models'): string
$module->getNamespace(): string

// Dependencies
$module->getRequires(): array  // ['Core' => '^1.0']
$module->getConflicts(): array
$module->getExtra(): array

// Providers
$module->getProviders(): array

// Serialization
$module->toArray(): array
$module->toJson(): string
```

---

## Helper Functions

```php
// Get module path
module_path(string $module, ?string $path = null): string

// Check if module is enabled
module_enabled(string $name): bool

// Get module asset URL
module_asset(string $module, string $path): string
```
