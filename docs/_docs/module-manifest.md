---
layout: docs
title: Module Manifest
description: The module.json configuration file
---

## Overview

Every module requires a `module.json` file in its root directory. This manifest defines the module's identity, dependencies, and configuration.

## Full Schema

```json
{
    "name": "Blog",
    "alias": "blog",
    "description": "A full-featured blog management system",
    "version": "1.0.0",
    "priority": 0,
    "providers": [
        "Modules\\Blog\\Providers\\BlogServiceProvider"
    ],
    "requires": {
        "Core": "^1.0",
        "Media": ">=2.0"
    },
    "conflicts": {
        "LegacyBlog": "*"
    },
    "extra": {
        "routes": {
            "prefix": "blog",
            "middleware": ["web", "auth"]
        }
    }
}
```

## Required Fields

### name

The module's unique identifier. Must be PascalCase:

```json
{
    "name": "Blog"
}
```

### version

Semantic version number:

```json
{
    "version": "1.0.0"
}
```

## Optional Fields

### alias

Short identifier for namespacing views, routes, etc:

```json
{
    "alias": "blog"
}
```

If not specified, defaults to lowercase module name.

### description

Human-readable description:

```json
{
    "description": "Blog management with posts, categories, and tags"
}
```

### priority

Boot order (higher = earlier). Default is 0:

```json
{
    "priority": 100
}
```

Use cases:
- **High priority (100+)**: Core modules that others depend on
- **Normal (0)**: Most modules
- **Low (-100)**: Modules that run after others

### providers

Service providers to register:

```json
{
    "providers": [
        "Modules\\Blog\\Providers\\BlogServiceProvider",
        "Modules\\Blog\\Providers\\EventServiceProvider"
    ]
}
```

### requires

Module dependencies with version constraints:

```json
{
    "requires": {
        "Core": "^1.0",
        "Media": ">=2.0",
        "Categories": "~1.5"
    }
}
```

Version constraint syntax:
- `^1.0` - Compatible with 1.x (>=1.0.0 <2.0.0)
- `~1.5` - Allows patch updates (>=1.5.0 <1.6.0)
- `>=2.0` - 2.0 or higher
- `*` - Any version

### conflicts

Modules that cannot be enabled together:

```json
{
    "conflicts": {
        "LegacyBlog": "*",
        "OldPosts": "^1.0"
    }
}
```

### extra

Custom configuration passed to bridges:

```json
{
    "extra": {
        "routes": {
            "prefix": "api/v2",
            "middleware": ["api", "throttle:60,1"],
            "domain": "api.example.com"
        },
        "filament": {
            "navigation_group": "Content"
        }
    }
}
```

## Validation

Validate your manifest:

```bash
php artisan modular:validate Blog
```

Common validation errors:
- Missing required fields (`name`, `version`)
- Invalid version format
- Non-existent provider classes
- Circular dependencies

## Best Practices

### 1. Use Semantic Versioning

```json
{
    "version": "1.2.3"
}
```

- **Major** (1.x.x): Breaking changes
- **Minor** (x.2.x): New features, backward compatible
- **Patch** (x.x.3): Bug fixes

### 2. Specify Exact Dependencies

```json
{
    "requires": {
        "Core": "^1.0"
    }
}
```

Don't use `*` for dependencies in production.

### 3. Document Your Module

```json
{
    "description": "Handles user authentication including login, registration, password reset, and two-factor authentication",
    "keywords": ["auth", "login", "security"]
}
```
