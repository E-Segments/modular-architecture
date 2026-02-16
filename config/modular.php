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
    | Config Merging
    |--------------------------------------------------------------------------
    |
    | When enabled, module configuration files will be automatically merged
    | into Laravel's config repository. Each module's config/config.php or
    | config/{module_alias}.php will be accessible via config('{module_alias}.key').
    |
    | Example:
    |   Modules/Products/config/products.php with ['low_stock_threshold' => 5]
    |   Access via: config('products.low_stock_threshold')
    |
    */

    'config_merge' => [
        'enabled' => env('MODULAR_CONFIG_MERGE', true),
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

    /*
    |--------------------------------------------------------------------------
    | Framework Bridges
    |--------------------------------------------------------------------------
    |
    | Bridges auto-discover and register framework-specific components from
    | enabled modules. Each bridge integrates with a specific Laravel
    | ecosystem framework (Livewire, Filament, etc.).
    |
    | Enable bridges as needed. Bridges only activate when their required
    | framework is installed.
    |
    */

    'bridges' => [

        /*
        |--------------------------------------------------------------------------
        | Bridge Cache
        |--------------------------------------------------------------------------
        |
        | Cache discovered bridge components for faster boot in production.
        | Run `php artisan modular:bridges:cache` to warm the cache.
        | Run `php artisan modular:bridges:clear` to clear it.
        |
        */

        'cache' => [
            'enabled' => env('MODULAR_BRIDGE_CACHE', false),
            'driver' => env('MODULAR_BRIDGE_CACHE_DRIVER', 'file'), // 'file' or 'cache'
        ],

        /*
        |--------------------------------------------------------------------------
        | Livewire Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-discovers and registers Livewire components from modules.
        |
        | Components in Modules/{Name}/app/Livewire/*.php are registered
        | with a module prefix: <livewire:products::product-table />
        |
        */

        'livewire' => [
            'enabled' => env('MODULAR_BRIDGE_LIVEWIRE', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Filament Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-discovers Filament resources, pages, and widgets from modules.
        |
        | Components in:
        | - Modules/{Name}/app/Filament/Resources/*.php
        | - Modules/{Name}/app/Filament/Pages/*.php
        | - Modules/{Name}/app/Filament/Widgets/*.php
        |
        */

        'filament' => [
            'enabled' => env('MODULAR_BRIDGE_FILAMENT', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Routes Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-loads route files from modules:
        | - routes/web.php -> Web routes with 'web' middleware
        | - routes/api.php -> API routes with 'api' middleware
        | - routes/console.php -> Console commands
        | - routes/channels.php -> Broadcast channels
        |
        */

        'routes' => [
            'enabled' => env('MODULAR_BRIDGE_ROUTES', false),
            'prefix_web' => true,                           // Prefix web routes with module name
            'prefix_api' => true,                           // Prefix API routes with module name
            'api_prefix' => 'api',                          // API route prefix
            'web_middleware' => ['web'],                    // Middleware for web routes
            'api_middleware' => ['api'],                    // Middleware for API routes
        ],

        /*
        |--------------------------------------------------------------------------
        | Events Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-discovers event listeners and subscribers from modules.
        |
        | Discovery paths:
        | - Modules/{Name}/app/Listeners/*.php -> Event listeners
        | - Modules/{Name}/app/Subscribers/*.php -> Event subscribers
        |
        | Listeners are matched to events by their handle() method signature.
        |
        */

        'events' => [
            'enabled' => env('MODULAR_BRIDGE_EVENTS', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Blade Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-registers Blade views and components from modules.
        |
        | Features:
        | - View namespaces: @include('products::partials.card')
        | - Anonymous components: <x-products::button />
        | - Class components: Modules/{Name}/app/View/Components/*.php
        |
        */

        'blade' => [
            'enabled' => env('MODULAR_BRIDGE_BLADE', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Schedule Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-discovers and registers scheduled tasks from modules.
        |
        | Supported patterns:
        | - Modules/{Name}/app/Console/Schedule.php with __invoke($schedule)
        | - Modules/{Name}/app/Console/Kernel.php with schedule($schedule)
        |
        */

        'schedule' => [
            'enabled' => env('MODULAR_BRIDGE_SCHEDULE', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Migration Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-loads migrations from modules.
        |
        | Discovery path: database/migrations/*.php
        |
        | Migrations are registered with Laravel's migrator and available
        | to `php artisan migrate`.
        |
        */

        'migrations' => [
            'enabled' => env('MODULAR_BRIDGE_MIGRATIONS', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Translation Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-loads translations from modules.
        |
        | Discovery paths:
        | - lang/*.php (PHP translation files)
        | - lang/*.json (JSON translation files)
        | - lang/{locale}/*.php (Locale-specific files)
        |
        | Access via: trans('products::messages.created')
        |
        */

        'translations' => [
            'enabled' => env('MODULAR_BRIDGE_TRANSLATIONS', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Observer Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-discovers and registers model observers from modules.
        |
        | Discovery path: app/Observers/*.php
        |
        | Convention: ProductObserver observes Product model
        |
        */

        'observers' => [
            'enabled' => env('MODULAR_BRIDGE_OBSERVERS', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Policy Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-discovers and registers authorization policies from modules.
        |
        | Discovery path: app/Policies/*.php
        |
        | Convention: ProductPolicy authorizes Product model
        |
        */

        'policies' => [
            'enabled' => env('MODULAR_BRIDGE_POLICIES', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Command Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-discovers and registers Artisan console commands from modules.
        |
        | Discovery path: app/Console/Commands/*.php
        |
        */

        'commands' => [
            'enabled' => env('MODULAR_BRIDGE_COMMANDS', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Middleware Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-discovers and registers HTTP middleware from modules.
        |
        | Discovery path: app/Http/Middleware/*.php
        |
        | Middleware is aliased as: products.check-stock
        | Define static $alias property to customize the alias.
        | Define static $global = true for global middleware.
        | Define static $groups = ['web'] to add to groups.
        |
        */

        'middleware' => [
            'enabled' => env('MODULAR_BRIDGE_MIDDLEWARE', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Service Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-discovers and binds service contracts to implementations.
        |
        | Discovery paths:
        | - app/Contracts/*.php -> Interface definitions
        | - app/Services/*.php -> Service implementations
        |
        | Binding conventions:
        | - ProductServiceContract -> ProductService
        | - ProductRepositoryContract -> ProductRepository
        |
        | *Repository, *Manager, *Factory are bound as singletons.
        | *Service, *Handler are bound as regular bindings.
        |
        */

        'services' => [
            'enabled' => env('MODULAR_BRIDGE_SERVICES', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Config Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-merges module configuration files with deep merging support
        | and environment-specific overrides.
        |
        | Discovery paths:
        | - config/*.php -> Module configuration files
        | - config/{env}.php -> Environment-specific overrides (testing.php, production.php)
        |
        */

        'config' => [
            'enabled' => env('MODULAR_BRIDGE_CONFIG', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Asset Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-publishes and symlinks module static assets.
        |
        | Discovery paths:
        | - public/ -> Public assets
        | - resources/assets/ -> Development assets
        |
        | Published to: public/modules/{module-name}/
        |
        */

        'assets' => [
            'enabled' => env('MODULAR_BRIDGE_ASSETS', false),
            'symlink' => env('MODULAR_BRIDGE_ASSETS_SYMLINK', false), // Use symlinks for dev
            'auto_publish' => env('MODULAR_BRIDGE_ASSETS_AUTO_PUBLISH', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Link Bridge
        |--------------------------------------------------------------------------
        |
        | Auto-discovers link definition files from modules for the new
        | fluent Link Registry system.
        |
        | Discovery path: app/Links/*.php
        |
        | Link definitions replace the boilerplate of ModuleLinkServiceProvider
        | with a clean, declarative API for cross-module relationships, macros,
        | and Filament integrations.
        |
        | Note: Actual link loading and booting is handled by the Core module's
        | LinkBootstrapper. This bridge provides discovery and tracking for
        | integration with the modular architecture system.
        |
        */

        'link' => [
            'enabled' => env('MODULAR_BRIDGE_LINK', true),
        ],

    ],

];
