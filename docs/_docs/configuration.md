---
layout: docs
title: Configuration
description: Complete configuration reference
---

## Publishing Config

```bash
php artisan vendor:publish --tag=modular-config
```

## Full Configuration

```php
// config/modular.php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Paths
    |--------------------------------------------------------------------------
    |
    | Directories where modules are located.
    |
    */
    'paths' => [
        base_path('Modules'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Directories
    |--------------------------------------------------------------------------
    |
    | Directories to skip during module discovery.
    |
    */
    'exclude' => [
        'vendor',
        'node_modules',
        'tests',
        '.git',
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Modules
    |--------------------------------------------------------------------------
    |
    | Modules that cannot be disabled or removed.
    |
    */
    'protected' => [
        'Core',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | How module state is persisted.
    |
    */
    'storage' => [
        'driver' => env('MODULAR_STORAGE', 'file'), // 'file' or 'database'
        'path' => storage_path('modular'),
        'table' => 'modules',
        'connection' => null, // null = default connection
        'auto_create_table' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Module discovery caching for performance.
    |
    */
    'cache' => [
        'enabled' => env('MODULAR_CACHE', false),
        'driver' => env('MODULAR_CACHE_DRIVER', 'file'),
        'ttl' => 86400, // 24 hours
        'path' => storage_path('modular/cache'),
        'prefix' => 'modular',
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery Configuration
    |--------------------------------------------------------------------------
    */
    'discovery' => [
        'auto' => true,
        'logging' => env('MODULAR_DISCOVERY_LOGGING', false),
        'max_depth' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, throws exceptions for invalid modules.
    |
    */
    'strict' => env('MODULAR_STRICT', false),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable verbose logging for debugging.
    |
    */
    'debug' => env('MODULAR_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Command Overrides
    |--------------------------------------------------------------------------
    |
    | Override Laravel's make:* commands to support --module flag.
    |
    */
    'commands' => [
        'override' => env('MODULAR_OVERRIDE_COMMANDS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Support
    |--------------------------------------------------------------------------
    */
    'octane' => [
        'refresh_on_tick' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub Integration
    |--------------------------------------------------------------------------
    |
    | For installing modules from GitHub repositories.
    |
    */
    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'default_branch' => 'main',
        'timeout' => 30,
        'verify_ssl' => true,
        'temp_path' => storage_path('modular/temp'),
        'backup_path' => storage_path('modular/backups'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scaffolding Configuration
    |--------------------------------------------------------------------------
    |
    | Defaults for module generation.
    |
    */
    'scaffolding' => [
        'namespace' => 'Modules',
        'vendor' => null,
        'author_name' => null,
        'author_email' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bridge Configuration
    |--------------------------------------------------------------------------
    */
    'bridges' => [
        'cache' => [
            'enabled' => env('MODULAR_BRIDGE_CACHE', false),
            'driver' => 'file',
        ],

        'routes' => [
            'enabled' => true,
            'prefix' => null,
            'middleware' => ['web'],
            'api_prefix' => 'api',
            'api_middleware' => ['api'],
        ],

        'blade' => [
            'enabled' => true,
        ],

        'livewire' => [
            'enabled' => true,
        ],

        'filament' => [
            'enabled' => true,
        ],

        'events' => [
            'enabled' => true,
        ],

        'migrations' => [
            'enabled' => true,
        ],

        'translations' => [
            'enabled' => true,
        ],

        'observers' => [
            'enabled' => true,
        ],

        'policies' => [
            'enabled' => true,
        ],

        'commands' => [
            'enabled' => true,
        ],

        'middleware' => [
            'enabled' => true,
        ],

        'services' => [
            'enabled' => true,
        ],

        'schedule' => [
            'enabled' => true,
        ],

        'config' => [
            'enabled' => false, // Deep merge, disabled by default
        ],

        'assets' => [
            'enabled' => true,
            'symlink' => env('MODULAR_ASSET_SYMLINK', true),
            'auto_publish' => false,
        ],

        'link' => [
            'enabled' => true,
        ],
    ],
];
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `MODULAR_STORAGE` | `file` | Storage driver |
| `MODULAR_CACHE` | `false` | Enable discovery cache |
| `MODULAR_CACHE_DRIVER` | `file` | Cache driver |
| `MODULAR_STRICT` | `false` | Strict mode |
| `MODULAR_DEBUG` | `false` | Debug logging |
| `MODULAR_OVERRIDE_COMMANDS` | `true` | Override Laravel commands |
| `MODULAR_BRIDGE_CACHE` | `false` | Cache bridge discovery |
| `MODULAR_ASSET_SYMLINK` | `true` | Symlink assets in dev |
| `GITHUB_TOKEN` | `null` | GitHub API token |

## Production Optimization

For production, enable caching:

```env
MODULAR_CACHE=true
MODULAR_BRIDGE_CACHE=true
```

Then run:

```bash
php artisan modular:cache
php artisan modular:bridges:cache
```
