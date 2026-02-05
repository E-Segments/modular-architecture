<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Module Types
    |--------------------------------------------------------------------------
    |
    | Define the types of modules your application supports. Each type can
    | have different scaffolding options and visual identifiers.
    |
    */

    'types' => [
        'module' => [
            'scaffold' => ['Providers'],
            'color' => 'blue',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery Paths
    |--------------------------------------------------------------------------
    |
    | Define the paths where modules are located. Multiple paths are supported
    | allowing you to organize modules in different directories.
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
    | Directories to exclude when scanning for modules.
    |
    */

    'exclude' => ['vendor', 'node_modules', 'tests', '.git'],

    /*
    |--------------------------------------------------------------------------
    | Protected Modules
    |--------------------------------------------------------------------------
    |
    | Modules listed here cannot be disabled or removed. This is useful for
    | core modules that the application depends on.
    |
    */

    'protected' => [
        // 'Core',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how module states and metadata are stored. You can choose
    | between 'file' (filesystem) or 'database' storage drivers.
    |
    | Supported drivers: "file", "database"
    |
    */

    'storage' => [
        'driver' => env('MODULAR_STORAGE_DRIVER', 'file'),

        // Filesystem storage settings
        'path' => env('MODULAR_STORAGE_PATH', storage_path('modular')),
        'states_file' => env('MODULAR_STATES_FILE', storage_path('modular/modules_statuses.json')),
        'metadata_file' => env('MODULAR_METADATA_FILE', storage_path('modular/modules_metadata.json')),

        // Database storage settings
        'table' => env('MODULAR_STORAGE_TABLE', 'modules'),
        'connection' => env('MODULAR_STORAGE_CONNECTION'), // null = default connection

        // Auto-create table if using database driver
        'auto_create_table' => env('MODULAR_AUTO_CREATE_TABLE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure how module discovery and resolution results are cached.
    |
    */

    'cache' => [
        'enabled' => env('MODULAR_CACHE', true),
        'driver' => env('MODULAR_CACHE_DRIVER', 'file'),
        'ttl' => env('MODULAR_CACHE_TTL', 86400), // 24 hours
        'path' => env('MODULAR_CACHE_PATH', storage_path('modular/cache')),
        'prefix' => env('MODULAR_CACHE_PREFIX', 'modular'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery Settings
    |--------------------------------------------------------------------------
    |
    | Configure how modules are discovered and loaded.
    |
    */

    'discovery' => [
        'auto' => env('MODULAR_DISCOVERY_AUTO', true),
        'logging' => env('MODULAR_DISCOVERY_LOGGING', false),
        'max_depth' => env('MODULAR_DISCOVERY_MAX_DEPTH', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub Integration
    |--------------------------------------------------------------------------
    |
    | Configure GitHub integration for installing modules from repositories.
    |
    */

    'github' => [
        // GitHub personal access token for private repos and higher rate limits
        'token' => env('MODULAR_GITHUB_TOKEN'),

        // Default branch to use when not specified
        'default_branch' => env('MODULAR_GITHUB_BRANCH', 'main'),

        // HTTP request timeout (seconds)
        'timeout' => env('MODULAR_GITHUB_TIMEOUT', 30),

        // Verify SSL certificates
        'verify_ssl' => env('MODULAR_GITHUB_VERIFY_SSL', true),

        // Temporary files directory
        'temp_path' => env('MODULAR_TEMP_PATH', storage_path('modular/temp')),

        // Backup directory for module updates
        'backup_path' => env('MODULAR_BACKUP_PATH', storage_path('modular/backups')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scaffolding Defaults
    |--------------------------------------------------------------------------
    |
    | Default values used when generating new modules.
    |
    */

    'scaffolding' => [
        'namespace' => env('MODULAR_NAMESPACE', 'Modules'),
        'vendor' => env('MODULAR_VENDOR', 'esegments'),
        'author_name' => env('MODULAR_AUTHOR_NAME'),
        'author_email' => env('MODULAR_AUTHOR_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, missing dependencies will cause exceptions. Useful for
    | CI/CD pipelines to catch issues early.
    |
    */

    'strict' => env('MODULAR_STRICT', false),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable additional logging and debugging output.
    |
    */

    'debug' => env('MODULAR_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Command Overrides
    |--------------------------------------------------------------------------
    |
    | When enabled, Laravel's make:* commands will be extended with a --module
    | option allowing you to generate classes directly into modules.
    |
    | Supported commands: make:model, make:controller, make:migration,
    | make:seeder, make:factory, make:policy, make:event, make:listener,
    | make:job, make:notification, make:mail, make:rule, make:request,
    | make:resource, make:test
    |
    */

    'commands' => [
        'override' => env('MODULAR_COMMANDS_OVERRIDE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Octane Support
    |--------------------------------------------------------------------------
    |
    | Configuration for running under Laravel Octane. The package uses static
    | caching for optimal performance in long-running processes.
    |
    | - refresh_on_tick: Set to true to refresh module cache on Octane tick
    |   events. This is useful if modules are frequently enabled/disabled.
    |
    */

    'octane' => [
        'refresh_on_tick' => env('MODULAR_OCTANE_REFRESH_ON_TICK', false),
    ],

];
