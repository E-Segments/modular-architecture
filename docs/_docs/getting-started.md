---
title: "Getting Started"
description: "Learn how to install and configure Modular Architecture"
order: 1
---

## Requirements

- PHP 8.2+
- Laravel 11+

## Installation

Install via Composer:

```bash
composer require esegments/modular-architecture
```

Publish the configuration:

```bash
php artisan vendor:publish --provider="Esegments\ModularArchitecture\ModularServiceProvider"
```

This creates `config/modular.php` with all available options.

## Creating Your First Module

Use the Artisan command to scaffold a new module:

```bash
php artisan modular:make-module Products
```

This creates a complete module structure:

```
Modules/Products/
├── app/
│   ├── Models/
│   ├── Providers/
│   │   └── ProductsServiceProvider.php
│   ├── Http/
│   │   └── Controllers/
│   └── ...
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── config/
│   └── config.php
├── routes/
│   ├── web.php
│   └── api.php
├── resources/
│   └── views/
├── tests/
├── composer.json
└── module.json
```

## Module Manifest

Every module has a `module.json` manifest:

```json
{
  "name": "Products",
  "alias": "products",
  "version": "1.0.0",
  "description": "Product management module",
  "providers": [
    "Modules\\Products\\Providers\\ProductsServiceProvider"
  ],
  "dependencies": {
    "Core": "^1.0"
  }
}
```

## Enabling Bridges

By default, bridges are disabled. Enable them in `config/modular.php`:

```php
'bridges' => [
    'route' => true,
    'blade' => true,
    'migration' => true,
    'config' => true,
    'translation' => true,
    'command' => true,
    'observer' => true,
    'policy' => true,
    'service' => true,
],
```

## Available Commands

| Command | Description |
|---------|-------------|
| `modular:list` | List all modules |
| `modular:enable {module}` | Enable a module |
| `modular:disable {module}` | Disable a module |
| `modular:status` | Show module status overview |
| `modular:health {module?}` | Check module health |
| `modular:migrate {module}` | Run module migrations |
| `modular:seed {module}` | Run module seeders |
| `modular:make-module {name}` | Create a new module |
| `modular:make-service {name}` | Create a service class |
| `modular:make-repository {name}` | Create a repository |
| `modular:make-dto {name}` | Create a DTO |
| `modular:make-action {name}` | Create an action class |

## Next Steps

- [Configuration](/docs/configuration/) - Learn about all configuration options
- [Bridges](/docs/bridges/) - Understand framework bridges
- [Link Registry](/docs/link-registry/) - Define cross-module relationships
- [Commands](/docs/commands/) - Explore all available commands
