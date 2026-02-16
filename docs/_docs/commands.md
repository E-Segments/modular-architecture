---
title: "Artisan Commands"
description: "All available Artisan commands"
order: 5
---

Modular Architecture provides 30+ Artisan commands for module management and code generation.

## Module Management

### modular:list

List all modules with their status:

```bash
php artisan modular:list

# Filter by status
php artisan modular:list --enabled
php artisan modular:list --disabled
```

### modular:status

Show detailed status of all modules:

```bash
php artisan modular:status

# Show specific module
php artisan modular:status Products
```

### modular:enable

Enable a module:

```bash
php artisan modular:enable Products

# Enable multiple
php artisan modular:enable Products Brands Categories
```

### modular:disable

Disable a module:

```bash
php artisan modular:disable Products

# Force disable (ignore dependents)
php artisan modular:disable Products --force
```

### modular:health

Check module health:

```bash
# Check all modules
php artisan modular:health

# Check specific module
php artisan modular:health Products

# Verbose output
php artisan modular:health Products -v
```

## Database Commands

### modular:migrate

Run module migrations:

```bash
# Migrate specific module
php artisan modular:migrate Products

# Migrate all modules
php artisan modular:migrate --all

# Rollback
php artisan modular:migrate Products --rollback

# Fresh migration
php artisan modular:migrate Products --fresh
```

### modular:seed

Run module seeders:

```bash
# Seed specific module
php artisan modular:seed Products

# Seed all modules
php artisan modular:seed --all

# Specific seeder class
php artisan modular:seed Products --class=ProductSeeder
```

## Asset Commands

### modular:publish-assets

Publish module assets:

```bash
# Publish specific module
php artisan modular:publish-assets Products

# Publish all modules
php artisan modular:publish-assets --all

# Force overwrite
php artisan modular:publish-assets Products --force
```

## Bridge Commands

### modular:bridges

List all bridges:

```bash
php artisan modular:bridges
```

### modular:bridges:inspect

Inspect a bridge:

```bash
php artisan modular:bridges:inspect route
php artisan modular:bridges:inspect config
```

### modular:bridges:cache

Cache bridge discovery:

```bash
php artisan modular:bridges:cache
```

### modular:bridges:clear

Clear bridge cache:

```bash
php artisan modular:bridges:clear
```

## Cache Commands

### modular:cache

Cache module discovery:

```bash
php artisan modular:cache

# Clear cache
php artisan modular:cache --clear
```

## Generator Commands

All generator commands follow the pattern:

```bash
php artisan modular:make-{type} {Name} {Module}
```

### modular:make-module

Create a new module:

```bash
php artisan modular:make-module Products

# With options
php artisan modular:make-module Products --model --migration --factory
```

### modular:make-service

Create a service class:

```bash
php artisan modular:make-service ProductService Products
```

### modular:make-repository

Create a repository:

```bash
php artisan modular:make-repository ProductRepository Products
```

### modular:make-dto

Create a Data Transfer Object:

```bash
php artisan modular:make-dto CreateProductData Products
```

### modular:make-action

Create an action class:

```bash
php artisan modular:make-action CreateProductAction Products
```

### modular:make-observer

Create a model observer:

```bash
php artisan modular:make-observer ProductObserver Products
```

### modular:make-enum

Create an enum:

```bash
php artisan modular:make-enum ProductStatus Products
```

### modular:make-cast

Create an Eloquent cast:

```bash
php artisan modular:make-cast MoneyCast Products
```

### modular:make-command

Create an Artisan command:

```bash
php artisan modular:make-command SyncProductsCommand Products
```

### modular:make-middleware

Create HTTP middleware:

```bash
php artisan modular:make-middleware CheckProductAccess Products
```

### modular:make-exception

Create an exception class:

```bash
php artisan modular:make-exception ProductNotFoundException Products
```

### More Generators

| Command | Creates |
|---------|---------|
| `modular:make-channel` | Broadcast channel |
| `modular:make-class` | Generic class |
| `modular:make-component` | Blade component |
| `modular:make-helper` | Helper class |
| `modular:make-interface` | Contract/Interface |
| `modular:make-provider` | Service provider |
| `modular:make-scope` | Query scope |
| `modular:make-trait` | Trait |
| `modular:make-view` | Blade view |

## Common Options

Most commands support these options:

| Option | Description |
|--------|-------------|
| `--force` | Overwrite existing files |
| `--quiet` | Suppress output |
| `-v`, `-vv`, `-vvv` | Increase verbosity |
