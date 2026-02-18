---
layout: docs
title: Module Lifecycle
description: Enable, disable, install, update, and remove modules
---

## Module States

Modules can be in two states:

| State | Description |
|-------|-------------|
| **Enabled** | Module is active, its providers are loaded |
| **Disabled** | Module exists but is not loaded |

## Enabling Modules

### Via CLI

```bash
php artisan modular:enable Blog
```

### Programmatically

```php
use Esegments\ModularArchitecture\Facades\Modular;

Modular::enable('Blog');
```

### What Happens on Enable

1. Dependencies are validated
2. `BeforeModuleEnable` extension point fires
3. Module state is updated
4. `ModuleEnabled` extension point fires
5. Cache is cleared

## Disabling Modules

### Via CLI

```bash
php artisan modular:disable Blog
```

### Programmatically

```php
Modular::disable('Blog');
```

### What Happens on Disable

1. Dependents are checked (modules that depend on this one)
2. `BeforeModuleDisable` extension point fires
3. Module state is updated
4. `ModuleDisabled` extension point fires
5. Cache is cleared

### Protected Modules

Some modules cannot be disabled:

```php
// config/modular.php
'protected' => [
    'Core',
    'Admin',
],
```

```bash
php artisan modular:disable Core
# Error: Module [Core] is protected and cannot be disabled.
```

## Installing Modules

### From GitHub

```bash
php artisan modular:install vendor/repo
php artisan modular:install E-Segments/blog-module

# With specific version
php artisan modular:install vendor/repo --ver=1.2.0

# With specific branch
php artisan modular:install vendor/repo --branch=develop
```

### What Happens on Install

1. `BeforeModuleInstall` extension point fires
2. Module is downloaded from GitHub
3. Archive is extracted to Modules directory
4. Dependencies are validated
5. `ModuleInstalled` extension point fires
6. Module is auto-enabled (optional)

### GitHub Authentication

For private repos, set your token:

```env
GITHUB_TOKEN=your_personal_access_token
```

## Updating Modules

### Via CLI

```bash
php artisan modular:update Blog
```

### Check for Updates

```bash
php artisan modular:outdated
```

### What Happens on Update

1. Current module is backed up
2. New version is downloaded
3. Files are replaced
4. If update fails, backup is restored

## Removing Modules

### Via CLI

```bash
php artisan modular:remove Blog
```

### With Force

```bash
php artisan modular:remove Blog --force
```

### What Happens on Remove

1. Dependents are checked
2. `BeforeModuleUninstall` extension point fires
3. Module files are deleted
4. `ModuleUninstalled` extension point fires
5. Cache is cleared

## Running Migrations

### All Modules

```bash
php artisan modular:migrate
```

### Specific Module

```bash
php artisan modular:migrate Blog
```

### With Options

```bash
php artisan modular:migrate Blog --seed
php artisan modular:migrate --rollback
php artisan modular:migrate --reset
php artisan modular:migrate --refresh
```

### What Happens on Migrate

1. `BeforeModuleMigrate` extension point fires
2. Migrations run
3. `ModuleMigrated` extension point fires

## Running Seeders

```bash
php artisan modular:seed Blog
```

### What Happens on Seed

1. `BeforeModuleSeed` extension point fires
2. Seeders run
3. `ModuleSeeded` extension point fires

## Lifecycle Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    Module Lifecycle                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   ┌──────────┐     enable      ┌──────────┐                │
│   │ Disabled │ ──────────────▶ │ Enabled  │                │
│   └──────────┘                 └──────────┘                │
│        │                            │                       │
│        │ remove                     │ disable               │
│        ▼                            ▼                       │
│   ┌──────────┐                ┌──────────┐                 │
│   │ Removed  │                │ Disabled │                 │
│   └──────────┘                └──────────┘                 │
│                                                             │
│   Install ──▶ Disabled ──▶ Enable ──▶ Migrate ──▶ Seed    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Checking Module Status

```bash
php artisan modular:status Blog
```

Output:
```
Module: Blog
├── Version: 1.2.0
├── Status: Enabled
├── Protected: No
├── Path: /var/www/Modules/Blog
├── Namespace: Modules\Blog
├── Dependencies:
│   ├── Core: ^1.0 (satisfied)
│   └── Media: ^2.0 (satisfied)
└── Dependents:
    └── Comments (requires Blog ^1.0)
```
