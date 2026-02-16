---
title: "Configuration"
description: "All configuration options explained"
order: 4
---

The configuration file `config/modular.php` contains all options for customizing the modular architecture behavior.

## Basic Configuration

```php
return [
    // Path to modules directory
    'path' => base_path('Modules'),

    // Module namespace
    'namespace' => 'Modules',

    // Auto-discover modules on boot
    'auto_discover' => true,

    // Cache module discovery
    'cache' => [
        'enabled' => env('MODULAR_CACHE', true),
        'key' => 'modular.modules',
        'ttl' => 3600, // 1 hour
    ],
];
```

## Bridge Configuration

Enable or disable specific bridges:

```php
'bridges' => [
    'route' => [
        'enabled' => true,
        'prefix' => true,           // Prefix routes with module alias
        'middleware' => ['web'],    // Default middleware for web routes
        'api_prefix' => 'api',      // API route prefix
        'api_middleware' => ['api'],
        'versioned_api' => true,    // Support versioned APIs
    ],

    'blade' => [
        'enabled' => true,
        'hint_path' => true,        // Register view hints
        'components' => true,       // Register Blade components
    ],

    'migration' => [
        'enabled' => true,
        'path' => 'database/migrations',
    ],

    'config' => [
        'enabled' => true,
        'merge' => true,            // Deep merge with app config
        'environment' => true,      // Load environment-specific configs
    ],

    'translation' => [
        'enabled' => true,
        'path' => 'lang',
    ],

    'command' => [
        'enabled' => true,
        'path' => 'app/Commands',
    ],

    'observer' => [
        'enabled' => true,
        'auto_register' => true,    // Auto-detect model observers
    ],

    'policy' => [
        'enabled' => true,
        'auto_register' => true,    // Auto-detect model policies
    ],

    'service' => [
        'enabled' => true,
        'auto_bind' => true,        // Auto-bind contracts to implementations
    ],

    'livewire' => [
        'enabled' => true,
        'prefix' => true,           // Prefix component names
    ],

    'filament' => [
        'enabled' => true,
        'discover_resources' => true,
        'discover_pages' => true,
        'discover_widgets' => true,
    ],

    'asset' => [
        'enabled' => true,
        'publish_path' => 'modules',
        'symlink' => env('MODULAR_ASSET_SYMLINK', false),
    ],

    'link' => [
        'enabled' => true,
        'path' => 'app/Links',
    ],
],
```

## Storage Configuration

Configure how module state is stored:

```php
'storage' => [
    'driver' => 'file', // 'file' or 'database'

    'file' => [
        'path' => storage_path('modular'),
    ],

    'database' => [
        'connection' => null, // Use default
        'table' => 'module_states',
    ],
],
```

## Extension Points

Configure module lifecycle extensions:

```php
'extensions' => [
    'before_install' => [],
    'after_install' => [],
    'before_enable' => [],
    'after_enable' => [],
    'before_disable' => [],
    'after_disable' => [],
    'before_uninstall' => [],
    'after_uninstall' => [],
],
```

## Generator Configuration

Configure code generators:

```php
'generators' => [
    'paths' => [
        'model' => 'app/Models',
        'controller' => 'app/Http/Controllers',
        'service' => 'app/Services',
        'repository' => 'app/Repositories',
        'dto' => 'app/DTOs',
        'action' => 'app/Actions',
        'observer' => 'app/Observers',
        'policy' => 'app/Policies',
        'enum' => 'app/Enums',
        'event' => 'app/Events',
        'listener' => 'app/Listeners',
        'job' => 'app/Jobs',
        'command' => 'app/Commands',
        'middleware' => 'app/Http/Middleware',
        'request' => 'app/Http/Requests',
        'resource' => 'app/Http/Resources',
    ],

    'stubs' => [
        'path' => null, // Use package stubs
        'custom' => [],  // Custom stub overrides
    ],
],
```

## Health Check Configuration

Configure health checks:

```php
'health' => [
    'checks' => [
        'manifest' => true,
        'provider' => true,
        'config' => true,
        'migrations' => true,
        'dependencies' => true,
    ],
],
```

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `MODULAR_CACHE` | Enable module caching | `true` |
| `MODULAR_ASSET_SYMLINK` | Use symlinks for assets | `false` |
| `MODULAR_DEBUG` | Enable debug logging | `false` |

## Artisan Commands

```bash
# Publish config
php artisan vendor:publish --tag=modular-config

# Clear and rebuild cache
php artisan modular:cache

# Clear cache only
php artisan modular:cache --clear
```
