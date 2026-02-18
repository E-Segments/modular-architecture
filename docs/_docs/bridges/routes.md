---
layout: docs
title: Route Bridge
description: Auto-load module routes with versioning support
---

## Overview

The Route Bridge automatically discovers and loads route files from your modules, supporting web routes, API routes, versioned APIs, and domain-specific routing.

## Basic Usage

Create route files in your module's `routes/` directory:

```
Modules/Blog/
└── routes/
    ├── web.php
    ├── api.php
    ├── console.php
    └── channels.php
```

Routes are automatically loaded with the module's alias as prefix.

### Example: web.php

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\PostController;

Route::get('/', [PostController::class, 'index'])->name('posts.index');
Route::get('/{post}', [PostController::class, 'show'])->name('posts.show');
```

Resulting routes:
- `GET /blog` → `blog.posts.index`
- `GET /blog/{post}` → `blog.posts.show`

## API Versioning

Create versioned API routes:

```
Modules/Blog/
└── routes/
    └── api/
        ├── v1.php
        └── v2.php
```

### routes/api/v1.php

```php
Route::get('/posts', [PostController::class, 'index']);
```

Resulting route: `GET /api/v1/blog/posts`

### routes/api/v2.php

```php
Route::get('/posts', [PostControllerV2::class, 'index']);
Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
```

Resulting routes:
- `GET /api/v2/blog/posts`
- `GET /api/v2/blog/posts/{post}/comments`

## Domain Routing

For multi-domain applications:

```
Modules/Blog/
└── routes/
    └── domains/
        ├── admin.php
        └── api.php
```

Configure domains in `config/modular.php`:

```php
'bridges' => [
    'routes' => [
        'domains' => [
            'admin' => 'admin.example.com',
            'api' => 'api.example.com',
        ],
    ],
],
```

## Configuration

### Global Configuration

```php
// config/modular.php
'bridges' => [
    'routes' => [
        'enabled' => true,
        'prefix' => null,              // Global prefix for all modules
        'middleware' => ['web'],       // Default middleware
        'api_prefix' => 'api',
        'api_middleware' => ['api'],
    ],
],
```

### Per-Module Configuration

Override in `module.json`:

```json
{
    "name": "Blog",
    "extra": {
        "routes": {
            "prefix": "content/blog",
            "middleware": ["web", "auth"],
            "api": {
                "prefix": "v1/content",
                "middleware": ["api", "auth:sanctum"]
            }
        }
    }
}
```

## Middleware

### Route Middleware

Apply middleware to specific routes:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

### Module-Level Middleware

Define in `module.json`:

```json
{
    "extra": {
        "routes": {
            "middleware": ["web", "auth", "blog.check-subscription"]
        }
    }
}
```

## Route Naming

Routes are automatically prefixed with the module alias:

```php
// In Modules/Blog/routes/web.php
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');

// Results in: blog.posts.index
route('blog.posts.index'); // /blog/posts
```

## Disabling Routes

### Disable Specific Route Files

Don't create the file, or return early:

```php
// routes/api.php
<?php

if (!config('blog.api_enabled')) {
    return;
}

Route::get('/posts', [PostController::class, 'index']);
```

### Disable All Routes for Module

In `module.json`:

```json
{
    "extra": {
        "routes": {
            "enabled": false
        }
    }
}
```

## Debugging Routes

List module routes:

```bash
php artisan route:list --path=blog
```

Inspect route bridge:

```bash
php artisan modular:bridges:inspect route
```
