---
layout: docs
title: Dependency Resolution
description: Managing module dependencies and load order
---

## Declaring Dependencies

In your `module.json`:

```json
{
    "name": "Blog",
    "requires": {
        "Core": "^1.0",
        "Media": ">=2.0",
        "Categories": "~1.5"
    }
}
```

## Version Constraints

| Constraint | Meaning |
|-----------|---------|
| `^1.0` | Compatible with 1.x (>=1.0.0 <2.0.0) |
| `~1.5` | Allows patch updates (>=1.5.0 <1.6.0) |
| `>=2.0` | 2.0 or higher |
| `>1.0 <3.0` | Between versions |
| `1.2.3` | Exact version |
| `*` | Any version |

## Load Order

Modules load in dependency order:

```
Core (no dependencies) → loads first
  ↓
Media (requires Core) → loads second
  ↓
Categories (requires Core) → loads third
  ↓
Blog (requires Core, Media, Categories) → loads last
```

The resolver uses **topological sorting** to determine the correct order.

## Checking Dependencies

### View Module Dependencies

```bash
php artisan modular:status Blog
```

### View What Depends on a Module

```bash
php artisan modular:dependents Core
```

Output:
```
Modules depending on Core:
├── Media (requires Core ^1.0)
├── Categories (requires Core ^1.0)
├── Blog (requires Core ^1.0)
└── Comments (requires Core ^1.0, Blog ^1.0)
```

## Programmatic Access

```php
use Esegments\ModularArchitecture\Facades\Modular;

// Get modules that depend on Core
$dependents = Modular::getDependents('Core');

// Get Core's dependencies
$dependencies = Modular::getDependencies('Core');

// Check if enabling is safe
$resolver = app(DependencyResolver::class);
$result = $resolver->canEnable('Blog');

if (!$result->isValid()) {
    foreach ($result->errors as $error) {
        echo $error;
    }
}
```

## Circular Dependencies

Circular dependencies are automatically detected:

```
Module A requires B
Module B requires C
Module C requires A  ← Circular!
```

Error:
```
CircularDependencyException: Circular dependency detected: A → B → C → A
```

### Detection

The resolver uses the `sweetchuck/cdd` library for cycle detection.

## Conflicts

Declare modules that cannot coexist:

```json
{
    "name": "NewAuth",
    "conflicts": {
        "LegacyAuth": "*",
        "OldAuth": "^1.0"
    }
}
```

If both modules are enabled, you'll get:

```
ConflictException: Module [NewAuth] conflicts with [LegacyAuth]
```

## Validation

### Validate All Modules

```bash
php artisan modular:validate
```

### Validate Specific Module

```bash
php artisan modular:validate Blog
```

### Validation Checks

1. **Manifest validity** - JSON structure, required fields
2. **Dependencies exist** - Required modules are installed
3. **Version compatibility** - Versions satisfy constraints
4. **No circular dependencies** - No dependency cycles
5. **No conflicts** - Conflicting modules not both enabled

## Safe Operations

### Safe to Disable?

Before disabling, check for dependents:

```php
$dependents = Modular::getDependents('Core');

if ($dependents->isNotEmpty()) {
    // Cannot safely disable - other modules depend on it
}
```

### Safe to Remove?

```php
$validator = app(DependencyValidator::class);
$result = $validator->canRemove('Blog');

if (!$result->isValid()) {
    // Cannot remove - would break dependencies
}
```

## Optional Dependencies

For soft dependencies (nice-to-have but not required):

```php
// In your service provider
public function boot(): void
{
    if (Modular::isEnabled('Analytics')) {
        // Register analytics integration
        $this->registerAnalytics();
    }
}
```

## Dependency Resolution Order

1. Parse all `module.json` files
2. Build dependency graph
3. Detect cycles (fail if found)
4. Topologically sort
5. Return ordered collection

```php
$resolver = app(DependencyResolver::class);
$ordered = $resolver->resolve($modules);

// $ordered is a ModuleCollection in load order
```
