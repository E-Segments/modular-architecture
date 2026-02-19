---
layout: docs
title: Octane Support
description: Using modular architecture with Laravel Octane
order: 53
parent: "Advanced"
---

## Overview

Laravel Octane keeps your application in memory between requests for improved performance. This package includes built-in support for Octane's lifecycle.

## Configuration

Enable Octane support:

```php
// config/modular.php
'octane' => [
    'refresh_on_tick' => false,
],
```

## How It Works

### State Warming

When an Octane worker starts, module state is warmed:

```php
// Happens automatically
$this->app->make(ModuleRegistry::class)->warmState();
```

### State Flushing

Between requests, any request-scoped state is flushed:

```php
// Happens automatically on each request
$this->app->make(ModuleRegistry::class)->flushRequestState();
```

### Tick Refresh

Optionally refresh module state on Octane tick events:

```php
'octane' => [
    'refresh_on_tick' => true, // Check for module changes
],
```

Use this if modules might be enabled/disabled while Octane is running.

## Cache Considerations

With Octane, be mindful of static caches:

```php
class MyService
{
    // Static cache persists across requests!
    private static array $cache = [];
    
    public function getData(): array
    {
        // This will persist in Octane
        return static::$cache;
    }
}
```

The package handles its internal caches, but your module code should:

1. Use Laravel's cache facade instead of static properties
2. Or register flush callbacks with Octane

### Registering Flush Callbacks

```php
use Laravel\Octane\Facades\Octane;

Octane::tick(function () {
    MyService::clearStaticCache();
});
```

## Module Hot Reloading

For development, you can enable module hot-reloading:

```php
'octane' => [
    'refresh_on_tick' => env('OCTANE_REFRESH_MODULES', false),
],
```

```env
# .env.local
OCTANE_REFRESH_MODULES=true
```

This checks for module.json changes on each tick.

## Best Practices

### 1. Avoid Static State in Modules

```php
// ❌ Bad: Static state persists
class OrderService
{
    private static ?Order $lastOrder = null;
}

// ✅ Good: Use container bindings
class OrderService
{
    private ?Order $lastOrder = null;
}
```

### 2. Use Proper Service Bindings

```php
// In ServiceProvider
$this->app->scoped(OrderService::class); // Scoped = new per request
```

### 3. Clear Caches Properly

```php
// Use Laravel's cache tags
Cache::tags(['orders'])->flush();

// Not static arrays
```

### 4. Test with Octane

```bash
php artisan octane:start --watch
```

Make requests and ensure module state is correct.

## Troubleshooting

### Stale Module State

If module changes aren't reflected:

1. Restart Octane workers
2. Or enable `refresh_on_tick`

```bash
php artisan octane:reload
```

### Memory Leaks

If memory grows over time:

1. Check for static caches in your modules
2. Review event listener accumulation
3. Enable Octane's garbage collection

```php
// config/octane.php
'garbage' => 50, // Collect every 50 requests
```

## Events

The package listens to these Octane events:

| Event | Action |
|-------|--------|
| `RequestReceived` | Warm module state |
| `RequestTerminated` | Flush request state |
| `TickReceived` | Refresh if enabled |
| `WorkerStarting` | Initialize module system |
| `WorkerStopping` | Cleanup |
