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

    'exclude' => ['vendor', 'node_modules', 'tests'],

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
        'path' => storage_path('framework/cache/modular'),
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
    ],

    /*
    |--------------------------------------------------------------------------
    | State File
    |--------------------------------------------------------------------------
    |
    | The file where module enabled/disabled states are stored.
    |
    */

    'state_file' => base_path('modules_statuses.json'),

    /*
    |--------------------------------------------------------------------------
    | Scaffolding Defaults
    |--------------------------------------------------------------------------
    |
    | Default values used when generating new modules.
    |
    */

    'scaffolding' => [
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

];
