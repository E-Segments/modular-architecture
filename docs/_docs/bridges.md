---
title: "Framework Bridges"
description: "Auto-discovery bridges for Laravel components"
order: 2
---

Framework Bridges automatically discover and register Laravel components from your modules. Each bridge handles a specific type of resource.

## Available Bridges

| Bridge | Discovers | Path Pattern |
|--------|-----------|--------------|
| RouteBridge | Routes | `routes/*.php`, `routes/api/v*.php` |
| BladeBridge | Views & Components | `resources/views/**/*.blade.php` |
| MigrationBridge | Migrations | `database/migrations/*.php` |
| ConfigBridge | Configuration | `config/*.php` |
| TranslationBridge | Translations | `lang/*.php`, `lang/*.json` |
| CommandBridge | Artisan Commands | `app/Commands/*.php` |
| ObserverBridge | Model Observers | `app/Observers/*Observer.php` |
| PolicyBridge | Model Policies | `app/Policies/*Policy.php` |
| ServiceBridge | Service Bindings | `app/Contracts/*Contract.php` |
| EventBridge | Event Classes | `app/Events/*.php` |
| ScheduleBridge | Scheduled Tasks | `app/Console/Kernel.php` |
| LivewireBridge | Livewire Components | `app/Livewire/*.php` |
| FilamentBridge | Filament Resources | `app/Filament/**/*.php` |
| AssetBridge | Static Assets | `public/`, `resources/assets/` |
| LinkBridge | Link Definitions | `app/Links/*.php` |

## Enabling Bridges

Configure bridges in `config/modular.php`:

```php
'bridges' => [
    'route' => true,
    'blade' => true,
    'migration' => true,
    'config' => true,
    'translation' => true,
    'command' => false, // Disabled
    'observer' => true,
    'policy' => true,
    'service' => true,
    'event' => true,
    'schedule' => false,
    'livewire' => true,
    'filament' => true,
    'asset' => true,
    'link' => true,
],
```

## Route Bridge

The Route Bridge supports versioned APIs and domain routing.

### Basic Routes

```
Modules/Products/routes/
├── web.php      # Web routes
├── api.php      # API routes
└── console.php  # Console routes
```

### Versioned APIs

```
Modules/Products/routes/
└── api/
    ├── v1.php   # /api/v1/products
    └── v2.php   # /api/v2/products
```

### Configuration

```php
'route' => [
    'enabled' => true,
    'prefix' => true,        // Prefix with module alias
    'middleware' => ['web'], // Default middleware
    'api_prefix' => 'api',
    'api_middleware' => ['api'],
],
```

## Config Bridge

The Config Bridge merges module configs with deep array merging.

### Basic Config

```php
// Modules/Products/config/config.php
return [
    'per_page' => 20,
    'cache_ttl' => 3600,
];
```

Access via: `config('products.per_page')`

### Environment Overrides

```
Modules/Products/config/
├── config.php         # Base config
├── testing.php        # Testing overrides
└── production.php     # Production overrides
```

## Blade Bridge

Registers views and components automatically.

### Views

```
Modules/Products/resources/views/
├── index.blade.php
├── show.blade.php
└── components/
    └── card.blade.php
```

Use in templates:

```blade
@include('products::index')

<x-products::card :product="$product" />
```

## Service Bridge

Auto-binds contracts to implementations.

### Convention

```php
// Contract: Modules/Products/app/Contracts/ProductRepositoryContract.php
// Implementation: Modules/Products/app/Repositories/ProductRepository.php
```

The bridge automatically binds `ProductRepositoryContract` to `ProductRepository`.

## Bridge Commands

```bash
# List all bridges and their status
php artisan modular:bridges

# Inspect a specific bridge
php artisan modular:bridges:inspect route

# Cache bridge discovery
php artisan modular:bridges:cache

# Clear bridge cache
php artisan modular:bridges:clear
```

## Creating Custom Bridges

Extend `AbstractBridge` to create custom bridges:

```php
use Esegments\ModularArchitecture\Bridges\AbstractBridge;

class CustomBridge extends AbstractBridge
{
    public function getName(): string
    {
        return 'custom';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function registerModule(Module $module): void
    {
        // Discovery and registration logic
    }

    public function boot(): void
    {
        // Boot logic
    }
}
```

Register in a service provider:

```php
$bridgeManager = app(BridgeManager::class);
$bridgeManager->extend('custom', fn() => new CustomBridge());
```
