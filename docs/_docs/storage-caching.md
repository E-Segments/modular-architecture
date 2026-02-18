---
layout: docs
title: Storage & Caching
description: Module state persistence and performance optimization
---

## Storage Drivers

Module state (enabled/disabled) is persisted using storage drivers.

### File Storage (Default)

State saved to JSON files:

```php
// config/modular.php
'storage' => [
    'driver' => 'file',
    'path' => storage_path('modular'),
],
```

Files created:
```
storage/modular/
├── modules_statuses.json    # Enabled/disabled states
└── modules_metadata.json    # Additional metadata
```

### Database Storage

For multi-server deployments:

```php
'storage' => [
    'driver' => 'database',
    'table' => 'modules',
    'connection' => null,  // Uses default connection
    'auto_create_table' => true,
],
```

Table schema:
```sql
CREATE TABLE modules (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) UNIQUE,
    enabled BOOLEAN DEFAULT true,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Migrating Between Drivers

```bash
php artisan modular:storage:migrate database
php artisan modular:storage:migrate file
```

## Module Cache

Cache module discovery for performance.

### Enable Caching

```php
'cache' => [
    'enabled' => true,
    'driver' => 'file',  // or 'redis', 'memcached'
    'ttl' => 86400,      // 24 hours
    'path' => storage_path('modular/cache'),
    'prefix' => 'modular',
],
```

### Cache Commands

```bash
# Build cache
php artisan modular:cache

# Clear cache
php artisan modular:cache:clear

# Optimize (cache + other optimizations)
php artisan modular:optimize
```

### When to Clear Cache

Clear cache when:
- Adding new modules
- Removing modules
- Changing `module.json`

The cache is automatically cleared when you:
- Enable/disable modules via CLI
- Install/update modules

## Bridge Caching

Bridges have separate caching:

```php
'bridges' => [
    'cache' => [
        'enabled' => true,
        'driver' => 'file',
    ],
],
```

```bash
# Cache bridge discovery
php artisan modular:bridges:cache

# Clear bridge cache
php artisan modular:bridges:clear
```

## Production Optimization

For production, enable all caching:

```bash
# Cache everything
php artisan modular:optimize
```

This runs:
1. `modular:cache`
2. `modular:bridges:cache`

Add to your deployment script:
```bash
php artisan modular:optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Cache Internals

### What's Cached

**Module Cache:**
- Module manifests (module.json)
- Module paths
- Dependency graphs
- Load order

**Bridge Cache:**
- Discovered routes per module
- Discovered views per module
- Discovered components per module
- etc.

### Cache Keys

```php
modular:modules           # All modules
modular:modules:enabled   # Enabled modules
modular:modules:order     # Load order
modular:bridges:route     # Route bridge data
modular:bridges:blade     # Blade bridge data
```

## Programmatic Access

```php
use Esegments\ModularArchitecture\Cache\ModuleCache;
use Esegments\ModularArchitecture\Storage\StorageManager;

// Storage
$storage = app(StorageManager::class);
$storage->setEnabled('Blog', true);
$storage->isEnabled('Blog');
$storage->getMetadata('Blog');

// Cache
$cache = app(ModuleCache::class);
$cache->put($modules);
$cache->get();
$cache->clear();
$cache->has();
```

## Performance Tips

### 1. Enable Caching in Production

```env
MODULAR_CACHE=true
MODULAR_BRIDGE_CACHE=true
```

### 2. Use Redis for Multi-Server

```php
'cache' => [
    'driver' => 'redis',
],
'storage' => [
    'driver' => 'database',
],
```

### 3. Warm Cache on Deploy

```bash
# In your deployment script
php artisan modular:cache
php artisan modular:bridges:cache
```

### 4. Use Database Storage for High-Availability

File storage can have race conditions with multiple servers. Database storage is atomic:

```php
'storage' => [
    'driver' => 'database',
],
```

## Debugging

### Check Cache Status

```bash
php artisan modular:bridges
```

Shows cache status at the bottom:
```
Cache: Cached (expires in 23h 45m)
```

### Force Fresh Discovery

```bash
php artisan modular:list --fresh
```

Bypasses cache for this command.
