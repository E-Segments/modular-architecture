---
layout: cli
title: CLI Reference
description: "Command-line interface for the Modular Architecture package"
cli_name: artisan
cli_version: "1.0.0"
cli_install: composer require esegments/modular-architecture

commands:
  - name: "modular:make"
    description: "Create a new module with optional scaffolding"
    arguments:
      - name: "name"
        description: "The name of the module"
        required: false
    options:
      - name: "--ver"
        description: "Module version (default: 1.0.0)"
      - name: "--description"
        description: "Module description"
      - name: "--interactive"
        description: "Use interactive mode"
      - name: "--model"
        description: "Generate a model"
      - name: "--controller"
        description: "Generate a controller"
      - name: "--views"
        description: "Generate views"
      - name: "--routes"
        description: "Generate routes"
      - name: "--config"
        description: "Generate config"
      - name: "--tests"
        description: "Generate tests"
      - name: "--all"
        description: "Generate all components"
    examples:
      - command: "php artisan modular:make Blog"
        description: "Create a basic module"
      - command: "php artisan modular:make Blog --all"
        description: "Create module with all components"
      - command: "php artisan modular:make"
        description: "Interactive mode"

  - name: "modular:list"
    description: "List all discovered modules"
    options:
      - name: "--enabled"
        description: "Show only enabled modules"
      - name: "--disabled"
        description: "Show only disabled modules"
    examples:
      - command: "php artisan modular:list"
        description: "List all modules"
      - command: "php artisan modular:list --enabled"
        description: "List enabled modules only"

  - name: "modular:enable"
    description: "Enable a module"
    arguments:
      - name: "name"
        description: "Module name to enable"
        required: true
    examples:
      - command: "php artisan modular:enable Blog"
        description: "Enable the Blog module"

  - name: "modular:disable"
    description: "Disable a module"
    arguments:
      - name: "name"
        description: "Module name to disable"
        required: true
    examples:
      - command: "php artisan modular:disable Blog"
        description: "Disable the Blog module"

  - name: "modular:status"
    description: "Show detailed status of a module"
    arguments:
      - name: "name"
        description: "Module name"
        required: true
    examples:
      - command: "php artisan modular:status Blog"
        description: "Show Blog module status"

  - name: "modular:validate"
    description: "Validate module configurations"
    arguments:
      - name: "name"
        description: "Module name (optional, validates all if not specified)"
        required: false
    examples:
      - command: "php artisan modular:validate"
        description: "Validate all modules"
      - command: "php artisan modular:validate Blog"
        description: "Validate Blog module"

  - name: "modular:dependents"
    description: "Show modules that depend on a given module"
    arguments:
      - name: "name"
        description: "Module name"
        required: true
    examples:
      - command: "php artisan modular:dependents Core"
        description: "Show what depends on Core"

  - name: "modular:health"
    description: "Run health checks on modules"
    examples:
      - command: "php artisan modular:health"
        description: "Run all health checks"

  - name: "modular:migrate"
    description: "Run migrations for modules"
    arguments:
      - name: "name"
        description: "Module name (optional)"
        required: false
    examples:
      - command: "php artisan modular:migrate"
        description: "Migrate all modules"
      - command: "php artisan modular:migrate Blog"
        description: "Migrate Blog module only"

  - name: "modular:seed"
    description: "Run seeders for modules"
    arguments:
      - name: "name"
        description: "Module name (optional)"
        required: false
    examples:
      - command: "php artisan modular:seed Blog"
        description: "Seed Blog module"

  - name: "modular:bridges"
    description: "List all framework bridges and their status"
    options:
      - name: "--enabled"
        description: "Show only enabled bridges"
      - name: "--available"
        description: "Show only available bridges"
    examples:
      - command: "php artisan modular:bridges"
        description: "List all bridges"
      - command: "php artisan modular:bridges --enabled"
        description: "List enabled bridges"

  - name: "modular:bridges:inspect"
    description: "Inspect a specific bridge in detail"
    arguments:
      - name: "name"
        description: "Bridge name"
        required: true
    examples:
      - command: "php artisan modular:bridges:inspect route"
        description: "Inspect the route bridge"

  - name: "modular:bridges:cache"
    description: "Cache bridge discovery data"
    examples:
      - command: "php artisan modular:bridges:cache"
        description: "Cache all bridge data"

  - name: "modular:bridges:clear"
    description: "Clear bridge cache"
    examples:
      - command: "php artisan modular:bridges:clear"
        description: "Clear bridge cache"

  - name: "modular:cache"
    description: "Cache module discovery"
    examples:
      - command: "php artisan modular:cache"
        description: "Cache module discovery"

  - name: "modular:cache:clear"
    description: "Clear module cache"
    examples:
      - command: "php artisan modular:cache:clear"
        description: "Clear module cache"

  - name: "modular:optimize"
    description: "Optimize modules for production"
    examples:
      - command: "php artisan modular:optimize"
        description: "Optimize all modules"

generators:
  title: "Generator Commands"
  description: "Commands to generate module components"
  commands:
    - name: "modular:make-service"
      creates: "Service class"
      location: "Services/"
    - name: "modular:make-repository"
      creates: "Repository class"
      location: "Repositories/"
    - name: "modular:make-dto"
      creates: "Data Transfer Object"
      location: "DTOs/"
    - name: "modular:make-action"
      creates: "Action class"
      location: "Actions/"
    - name: "modular:make-observer"
      creates: "Model observer"
      location: "Observers/"
    - name: "modular:make-enum"
      creates: "Enum class"
      location: "Enums/"
    - name: "modular:make-cast"
      creates: "Eloquent cast"
      location: "Casts/"
    - name: "modular:make-channel"
      creates: "Broadcast channel"
      location: "Broadcasting/"
    - name: "modular:make-class"
      creates: "Generic class"
      location: "Custom path"
    - name: "modular:make-command"
      creates: "Console command"
      location: "Commands/"
    - name: "modular:make-component"
      creates: "Blade component"
      location: "View/Components/"
    - name: "modular:make-exception"
      creates: "Exception class"
      location: "Exceptions/"
    - name: "modular:make-helper"
      creates: "Helper class"
      location: "Helpers/"
    - name: "modular:make-interface"
      creates: "Contract/Interface"
      location: "Contracts/"
    - name: "modular:make-middleware"
      creates: "HTTP middleware"
      location: "Middleware/"
    - name: "modular:make-provider"
      creates: "Service provider"
      location: "Providers/"
    - name: "modular:make-scope"
      creates: "Query scope"
      location: "Scopes/"
    - name: "modular:make-trait"
      creates: "Trait"
      location: "Concerns/"
    - name: "modular:make-view"
      creates: "Blade view"
      location: "resources/views/"
---

## Global Options

All commands support these global options:

| Option | Description |
|--------|-------------|
| `-h, --help` | Display help for the command |
| `-q, --quiet` | Do not output any message |
| `-V, --version` | Display application version |
| `--ansi` | Force ANSI output |
| `-n, --no-interaction` | Do not ask any interactive question |
| `-v, -vv, -vvv` | Increase verbosity level |

## Generator Command Format

All generator commands follow this pattern:

```bash
php artisan modular:make-{type} {name} {module}
```

| Argument | Description |
|----------|-------------|
| `name` | The name of the class to create |
| `module` | The target module name |

### Example

```bash
php artisan modular:make-service OrderService Orders
```

Creates `Modules/Orders/app/Services/OrderService.php`
