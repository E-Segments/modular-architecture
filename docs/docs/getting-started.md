---
layout: docs
title: Getting Started
description: Install and configure the Modular Architecture package
---

## Installation

Install the package via Composer:

```bash
composer require esegments/modular-architecture
```

## Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=modular-config
```

This creates `config/modular.php` with all available options.

## Directory Structure

By default, modules are stored in the `Modules/` directory:

```
your-app/
├── app/
├── Modules/
│   ├── Blog/
│   │   ├── app/
│   │   │   ├── Models/
│   │   │   ├── Providers/
│   │   │   └── ...
│   │   ├── config/
│   │   ├── database/
│   │   ├── resources/
│   │   ├── routes/
│   │   └── module.json
│   └── Shop/
│       └── ...
├── config/
│   └── modular.php
└── ...
```

## Creating Your First Module

Generate a new module using Artisan:

```bash
php artisan modular:make Blog
```

Or with additional components:

```bash
php artisan modular:make Blog --all
```

This creates:
- Model
- Controller
- Views
- Routes
- Config
- Tests

## Interactive Mode

For a guided experience:

```bash
php artisan modular:make
```

You'll be prompted for:
- Module name
- Version
- Description
- Components to generate

## Basic Usage

### List Modules

```bash
php artisan modular:list
```

### Enable/Disable Modules

```bash
php artisan modular:enable Blog
php artisan modular:disable Blog
```

### Check Status

```bash
php artisan modular:status Blog
```

## Using the Facade

```php
use Esegments\ModularArchitecture\Facades\Modular;

// Get all modules
$modules = Modular::all();

// Get enabled modules
$enabled = Modular::enabled();

// Find a specific module
$blog = Modular::find('Blog');

// Check if module exists
if (Modular::exists('Blog')) {
    // ...
}

// Enable/disable programmatically
Modular::enable('Blog');
Modular::disable('Blog');
```

## Next Steps

- [Module Structure](/docs/module-structure/) - Learn about module organization
- [Framework Bridges](/docs/bridges/) - Auto-register routes, views, and more
- [Configuration](/docs/configuration/) - Full configuration reference
