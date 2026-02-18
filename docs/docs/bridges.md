---
layout: docs
title: Framework Bridges
description: Auto-register module components with Laravel
---

## Overview

Bridges automatically discover and register module components with their respective Laravel ecosystems. Instead of manually registering routes, views, or commands in your service provider, bridges handle this automatically.

## Available Bridges

| Bridge | Purpose | Auto-discovers |
|--------|---------|----------------|
| **RouteBridge** | HTTP routing | `routes/*.php` |
| **BladeBridge** | Views & components | `resources/views/`, `app/View/Components/` |
| **LivewireBridge** | Livewire components | `app/Livewire/` |
| **FilamentBridge** | Filament resources | `app/Filament/` |
| **EventBridge** | Listeners & subscribers | `app/Listeners/`, `app/Subscribers/` |
| **MigrationBridge** | Database migrations | `database/migrations/` |
| **TranslationBridge** | Language files | `lang/` |
| **ObserverBridge** | Model observers | `app/Observers/` |
| **PolicyBridge** | Authorization policies | `app/Policies/` |
| **CommandBridge** | Artisan commands | `app/Console/Commands/` |
| **MiddlewareBridge** | HTTP middleware | `app/Http/Middleware/` |
| **ServiceBridge** | Service bindings | `app/Contracts/`, `app/Services/` |
| **ScheduleBridge** | Scheduled tasks | `app/Console/Schedule.php` |
| **ConfigBridge** | Configuration | `config/*.php` |
| **AssetBridge** | Static assets | `public/`, `resources/assets/` |

## Checking Bridge Status

```bash
php artisan modular:bridges
```

Output:
```
┌─────────────────┬───────────┬─────────┬─────────┐
│ Bridge          │ Available │ Status  │ Modules │
├─────────────────┼───────────┼─────────┼─────────┤
│ RouteBridge     │ ✓         │ Enabled │ 12      │
│ BladeBridge     │ ✓         │ Enabled │ 8       │
│ LivewireBridge  │ ✓         │ Enabled │ 5       │
│ FilamentBridge  │ ✓         │ Enabled │ 15      │
└─────────────────┴───────────┴─────────┴─────────┘
```

Inspect a specific bridge:

```bash
php artisan modular:bridges:inspect route
```

## Configuration

Enable or disable bridges in `config/modular.php`:

```php
'bridges' => [
    'routes' => [
        'enabled' => true,
        'prefix' => null,
        'middleware' => ['web'],
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
    // ...
],
```

## Bridge Caching

In production, cache bridge discovery:

```bash
php artisan modular:bridges:cache
```

Clear the cache:

```bash
php artisan modular:bridges:clear
```

## How Bridges Work

1. **Discovery**: Bridge scans configured directories in each module
2. **Registration**: Components are registered with Laravel
3. **Caching**: Discovery results are cached for performance

```php
// BridgeManager orchestrates all bridges
$manager = app(BridgeManager::class);

// Get all bridges
$bridges = $manager->all();

// Check if a bridge is available
$manager->get('route')->isAvailable();
```

## Creating Custom Bridges

Extend `AbstractBridge`:

```php
use Esegments\ModularArchitecture\Bridges\AbstractBridge;

class CustomBridge extends AbstractBridge
{
    public function name(): string
    {
        return 'CustomBridge';
    }

    public function isAvailable(): bool
    {
        return class_exists(SomePackage::class);
    }

    protected function registerModule(Module $module): void
    {
        // Discovery and registration logic
    }
}
```

Register in `config/modular.php`:

```php
'bridges' => [
    'custom' => [
        'enabled' => true,
        'class' => App\Bridges\CustomBridge::class,
    ],
],
```
