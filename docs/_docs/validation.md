---
layout: docs
title: Validation
description: Validate modules and their dependencies
---

## Module Validation

The validation system checks modules for correctness and compatibility.

### Validate All Modules

```bash
php artisan modular:validate
```

### Validate Specific Module

```bash
php artisan modular:validate Blog
```

## What Gets Validated

### 1. Manifest Structure

- Valid JSON syntax
- Required fields present (`name`, `version`)
- Valid version format (semver)
- Valid provider class names

### 2. Dependencies

- Required modules exist
- Version constraints satisfied
- No circular dependencies
- No conflicts with enabled modules

### 3. File Structure

- Service providers exist and are valid
- Required directories present

## Programmatic Validation

```php
use Esegments\ModularArchitecture\Facades\Modular;
use Esegments\ModularArchitecture\Validation\ModuleValidator;
use Esegments\ModularArchitecture\Validation\DependencyValidator;

// Validate single module
$result = Modular::validate('Blog');

if (!$result->isValid()) {
    foreach ($result->errors as $error) {
        echo $error . "\n";
    }
}

// Validate all
$result = Modular::validateAll();

// Validate per module
$results = Modular::validateAllPerModule();
foreach ($results as $moduleName => $result) {
    if (!$result->isValid()) {
        echo "{$moduleName}: " . implode(', ', $result->errors) . "\n";
    }
}
```

## ValidationResult Object

```php
$result = Modular::validate('Blog');

// Check if valid
$result->isValid(): bool

// Get errors
$result->errors: array

// Merge with another result
$result->merge(ValidationResult $other): ValidationResult
```

## Dependency Validation

```php
use Esegments\ModularArchitecture\Validation\DependencyValidator;

$validator = app(DependencyValidator::class);

// Check if module can be enabled
$result = $validator->canEnable('Blog');

// Check if module can be disabled
$result = $validator->canDisable('Core');

// Check if module can be removed
$result = $validator->canRemove('Blog');

// Validate all dependencies
$result = $validator->validateAll($modules);
```

## Common Validation Errors

### Missing Dependency

```
Module [Blog] requires [Media] which is not installed.
```

**Fix**: Install the required module:
```bash
php artisan modular:install E-Segments/media-module
```

### Version Mismatch

```
Module [Blog] requires [Core ^2.0] but [1.5.0] is installed.
```

**Fix**: Update the dependency:
```bash
php artisan modular:update Core
```

### Circular Dependency

```
Circular dependency detected: A → B → C → A
```

**Fix**: Refactor module dependencies to break the cycle.

### Conflict

```
Module [NewAuth] conflicts with enabled module [LegacyAuth].
```

**Fix**: Disable the conflicting module:
```bash
php artisan modular:disable LegacyAuth
```

### Invalid Manifest

```
Module [Blog] has invalid module.json: Syntax error
```

**Fix**: Check JSON syntax in `module.json`.

### Provider Not Found

```
Module [Blog] provider class [Modules\Blog\Providers\BlogServiceProvider] does not exist.
```

**Fix**: Create the missing provider or update `module.json`.

## Health Checks

For deeper validation:

```bash
php artisan modular:health
```

Checks:
- Manifest validity
- Service provider validity
- Directory structure
- File permissions
- Dependency integrity
- Circular dependency detection

```php
use Esegments\ModularArchitecture\Health\ModuleHealthChecker;

$checker = app(ModuleHealthChecker::class);
$report = $checker->check('Blog');

// Full health check all modules
$fullReport = $checker->checkAll();
```

## CI/CD Integration

Add validation to your CI pipeline:

```yaml
# .github/workflows/test.yml
- name: Validate Modules
  run: php artisan modular:validate --strict
```

The `--strict` flag causes non-zero exit on any validation error.
