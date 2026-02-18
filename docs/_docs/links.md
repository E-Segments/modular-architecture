---
layout: docs
title: Link System
description: Cross-module relationships without tight coupling
---

## Overview

The Link System allows modules to define relationships between each other without creating hard dependencies. This keeps domain modules decoupled while still enabling rich cross-module functionality.

## The Problem

Without links, modules would need to import each other directly:

```php
// ❌ BAD: Products module directly imports Tax module
namespace Modules\Products\Models;

use Modules\Tax\Models\TaxClass; // Tight coupling!

class Product extends Model
{
    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }
}
```

This creates problems:
- Products now depends on Tax
- Can't disable Tax without breaking Products
- Circular dependencies become likely

## The Solution: Links

Links define relationships externally:

```php
// ✅ GOOD: Link module handles the relationship
namespace Modules\TaxProductLink\Links;

use Esegments\ModularArchitecture\Facades\Links;

Links::define('TaxProductLink')
    ->requires('Tax', 'Products')
    ->belongsTo(Product::class, 'taxClass', TaxClass::class, 'tax_class_id')
    ->hasMany(TaxClass::class, 'products', Product::class, 'tax_class_id');
```

## Creating Links

### Using Fluent API

```php
use Esegments\ModularArchitecture\Facades\Links;

Links::define('TaxProductLink')
    // Required modules - link only activates when both exist
    ->requires('Tax', 'Products')
    
    // Add belongsTo relationship to Product
    ->belongsTo(
        Product::class,      // Model to add relationship to
        'taxClass',          // Relationship name
        TaxClass::class,     // Related model
        'tax_class_id'       // Foreign key
    )
    
    // Add hasMany relationship to TaxClass
    ->hasMany(
        TaxClass::class,
        'products',
        Product::class,
        'tax_class_id'
    );
```

### Link Definition File

Create in `app/Links/` directory:

```
Modules/TaxProductLink/
└── app/
    └── Links/
        └── TaxProductLink.php
```

```php
namespace Modules\TaxProductLink\Links;

use Esegments\ModularArchitecture\Links\LinkDefinition;

class TaxProductLink extends LinkDefinition
{
    protected array $requires = ['Tax', 'Products'];

    public function define(): void
    {
        $this->belongsTo(Product::class, 'taxClass', TaxClass::class, 'tax_class_id');
        $this->hasMany(TaxClass::class, 'products', Product::class, 'tax_class_id');
    }
}
```

## Available Relationship Types

### belongsTo

```php
->belongsTo(Product::class, 'taxClass', TaxClass::class, 'tax_class_id')
```

### hasMany

```php
->hasMany(TaxClass::class, 'products', Product::class, 'tax_class_id')
```

### hasOne

```php
->hasOne(User::class, 'profile', Profile::class, 'user_id')
```

### belongsToMany

```php
->belongsToMany(
    Product::class, 
    'categories', 
    Category::class, 
    'category_product',  // Pivot table
    'product_id',        // Foreign pivot key
    'category_id'        // Related pivot key
)
```

## Adding Macros

Links can add macros to models:

```php
Links::define('TaxProductLink')
    ->requires('Tax', 'Products')
    
    // Add accessor macro
    ->macro(Product::class, 'taxClassName', function () {
        return $this->taxClass?->name;
    })
    
    // Add query scope macro
    ->macro(Product::class, 'scopeWithTax', function ($query) {
        return $query->whereNotNull('tax_class_id');
    });
```

Usage:

```php
$product->taxClassName;
Product::withTax()->get();
```

## Conditional Activation

Links only activate when all required modules are enabled:

```php
Links::define('TaxProductLink')
    ->requires('Tax', 'Products');  // Both must be enabled
```

If Tax module is disabled:
- Link doesn't boot
- Relationships aren't added
- No errors thrown

## Checking Link Status

```php
use Esegments\ModularArchitecture\Facades\Links;

// Get all registered links
Links::all();

// Get only enabled links
Links::enabled();

// Get disabled links
Links::disabled();

// Check specific link
Links::has('TaxProductLink');
Links::isEnabled('TaxProductLink');
```

## CLI Commands

```bash
# List all links
php artisan modular:links

# Check link status
php artisan modular:links:status TaxProductLink
```

## Best Practices

### 1. Keep Links Thin

Links should only define relationships, not business logic:

```php
// ✅ Good
->belongsTo(Product::class, 'taxClass', TaxClass::class)

// ❌ Bad - business logic in link
->macro(Product::class, 'calculateTax', function () {
    return $this->price * $this->taxClass->rate / 100;
})
```

### 2. Use Descriptive Names

```php
// ✅ Good
Links::define('TaxProductLink')
Links::define('CustomerOrderLink')

// ❌ Bad
Links::define('Link1')
Links::define('TPL')
```

### 3. Document Requirements

```php
Links::define('TaxProductLink')
    ->requires('Tax', 'Products')
    ->description('Adds tax class relationship to products');
```
