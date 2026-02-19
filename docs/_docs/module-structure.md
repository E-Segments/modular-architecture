---
layout: docs
title: Module Structure
description: Understanding the anatomy of a module
order: 11
parent: "Module System"
---

## Standard Module Structure

A complete module follows this structure:

```
Modules/Blog/
├── app/
│   ├── Console/
│   │   └── Commands/
│   ├── Contracts/
│   ├── DTOs/
│   ├── Events/
│   ├── Exceptions/
│   ├── Filament/
│   │   ├── Resources/
│   │   ├── Pages/
│   │   └── Widgets/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Listeners/
│   ├── Livewire/
│   ├── Models/
│   ├── Observers/
│   ├── Policies/
│   ├── Providers/
│   │   └── BlogServiceProvider.php
│   ├── Repositories/
│   ├── Services/
│   └── View/
│       └── Components/
├── config/
│   └── config.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── lang/
│   └── en/
├── resources/
│   └── views/
├── routes/
│   ├── api.php
│   ├── web.php
│   └── api/
│       ├── v1.php
│       └── v2.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── composer.json
└── module.json
```

## Minimal Module

Not all directories are required. A minimal module needs:

```
Modules/Blog/
├── app/
│   └── Providers/
│       └── BlogServiceProvider.php
└── module.json
```

## Key Files

### module.json

The manifest file that defines your module:

```json
{
    "name": "Blog",
    "alias": "blog",
    "description": "Blog management module",
    "version": "1.0.0",
    "priority": 0,
    "providers": [
        "Modules\\Blog\\Providers\\BlogServiceProvider"
    ],
    "requires": {
        "Core": "^1.0"
    }
}
```

### Service Provider

The entry point for your module:

```php
namespace Modules\Blog\Providers;

use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path('Blog', 'config/config.php'),
            'blog'
        );
    }

    public function boot(): void
    {
        // Module boots here
    }
}
```

## Namespacing

Modules use PSR-4 autoloading:

| Directory | Namespace |
|-----------|-----------|
| `app/Models` | `Modules\Blog\Models` |
| `app/Http/Controllers` | `Modules\Blog\Http\Controllers` |
| `app/Services` | `Modules\Blog\Services` |
| `database/factories` | `Modules\Blog\Database\Factories` |

## Best Practices

### 1. Keep Modules Focused

Each module should have a single responsibility:

```
✓ Blog - Handles blog posts
✓ Comments - Handles comments (separate module)

✗ BlogAndCommentsAndNewsletters - Too broad
```

### 2. Use Proper Namespacing

```php
// Good
namespace Modules\Blog\Services;

// Bad
namespace App\Modules\Blog\Services;
```

### 3. Leverage Bridges

Don't manually register routes, views, etc. Let bridges handle it:

```php
// Instead of manually loading routes in ServiceProvider:
// $this->loadRoutesFrom(module_path('Blog', 'routes/web.php'));

// Just create routes/web.php and the RouteBridge handles it
```

### 4. Define Dependencies

Always declare dependencies in `module.json`:

```json
{
    "requires": {
        "Core": "^1.0",
        "Media": "^2.0"
    }
}
```

## Helper Functions

```php
// Get module path
$path = module_path('Blog');
$path = module_path('Blog', 'config/config.php');

// Check if module is enabled
if (module_enabled('Blog')) {
    // ...
}
```
