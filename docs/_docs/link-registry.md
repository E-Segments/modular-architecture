---
title: "Link Registry"
description: "Define cross-module relationships without tight coupling"
order: 42
parent: "Links"
---

The Link Registry system provides a fluent API for defining cross-module relationships, macros, and Filament integrations without creating tight coupling between modules.

## Why Link Registry?

In a modular architecture, domain modules should not import each other directly. The Link Registry solves this by:

1. **Defining relationships dynamically** - Using `resolveRelationUsing()`
2. **Conditional activation** - Links only activate when all required modules are enabled
3. **Centralized configuration** - All cross-module concerns in one place
4. **No boilerplate** - Replace 8+ files per link with a single definition

## Creating a Link

Create a link file in any module's `app/Links/` directory:

```php
// Modules/Products/app/Links/ProductBrandLink.php

use Esegments\ModularArchitecture\Facades\Links;
use Modules\Products\Models\Product;
use Modules\Brands\Models\Brand;

Links::define('ProductBrandLink')
    ->requires('Products', 'Brands')

    // Relationships
    ->belongsTo(Product::class, 'brand', Brand::class, 'brand_id')
    ->hasMany(Brand::class, 'products', Product::class, 'brand_id')

    // Macros
    ->relatedNameAccessor(Product::class, 'brandName', 'brand', 'name')
    ->productCountMacro(Brand::class);
```

## Relationship Methods

### belongsTo

```php
->belongsTo(
    modelClass: Product::class,
    relationName: 'brand',
    relatedClass: Brand::class,
    foreignKey: 'brand_id',
    ownerKey: 'id'
)
```

### hasMany

```php
->hasMany(
    modelClass: Brand::class,
    relationName: 'products',
    relatedClass: Product::class,
    foreignKey: 'brand_id',
    localKey: 'id'
)
```

### hasOne

```php
->hasOne(
    modelClass: Product::class,
    relationName: 'primaryImage',
    relatedClass: ProductImage::class,
    foreignKey: 'product_id'
)
```

### belongsToMany

```php
->belongsToMany(
    modelClass: Product::class,
    relationName: 'categories',
    relatedClass: Category::class,
    table: 'category_product',
    foreignPivotKey: 'product_id',
    relatedPivotKey: 'category_id',
    pivotColumns: ['sort_order'],
    withTimestamps: true
)
```

### morphToMany

```php
->morphToMany(
    modelClass: Product::class,
    relationName: 'tags',
    relatedClass: Tag::class,
    morphName: 'taggable',
    table: 'taggables'
)
```

### morphedByMany

```php
->morphedByMany(
    modelClass: Tag::class,
    relationName: 'products',
    relatedClass: Product::class,
    morphName: 'taggable',
    table: 'taggables'
)
```

## Macro Methods

### relatedNameAccessor

Get a related model's attribute:

```php
->relatedNameAccessor(
    modelClass: Product::class,
    macroName: 'brandName',
    relationName: 'brand',
    attributeName: 'name'
)

// Usage: $product->brandName() // Returns brand name or null
```

### relatedNamesAccessor

Get comma-separated names from a collection:

```php
->relatedNamesAccessor(
    modelClass: Product::class,
    macroName: 'categoryNames',
    relationName: 'categories',
    nameColumn: 'name'
)

// Usage: $product->categoryNames() // "Electronics, Gadgets"
```

### hasRelationMacro

Check if a foreign key is set:

```php
->hasRelationMacro(
    modelClass: Product::class,
    macroName: 'hasBrand',
    foreignKey: 'brand_id'
)

// Usage: $product->hasBrand() // true/false
```

### filterScope

Filter by foreign key:

```php
->filterScope(
    modelClass: Product::class,
    scopeName: 'scopeOfBrand',
    foreignKey: 'brand_id'
)

// Usage: Product::ofBrand($brandId)->get()
```

### productCountMacro

N+1 optimized product count:

```php
->productCountMacro(
    modelClass: Brand::class,
    relationName: 'products'
)

// Usage: $brand->product_count
// Checks withCount first, then loaded relation, then queries
```

### customMacro

Define any custom macro:

```php
->customMacro(
    modelClass: Product::class,
    macroName: 'getDiscountedPrice',
    callback: function (float $discount): float {
        return $this->price * (1 - $discount);
    }
)
```

## Filament Integrations

### Eager Loading

```php
->eagerLoad(
    resourceClass: ProductResource::class,
    context: 'table', // 'table', 'form', 'view', or 'all'
    relations: ['brand', 'categories']
)
```

### WithCount

```php
->withCount(
    resourceClass: BrandResource::class,
    relations: ['products']
)
```

### Relation Managers

```php
->relationManager(
    resourceClass: BrandResource::class,
    managerClass: ProductsRelationManager::class
)
```

### Form Sections

```php
->formSection(
    resourceClass: ProductResource::class,
    callback: fn() => Section::make('Brand')
        ->schema([
            Select::make('brand_id')
                ->relationship('brand', 'name')
        ])
)
```

## Boot Callbacks

Run custom code when the link boots:

```php
->onBoot(function ($definition) {
    logger()->info("Link {$definition->getName()} booted!");
})
```

## Checking Link Status

```php
use Esegments\ModularArchitecture\Facades\Links;

// Check if a link exists
Links::has('ProductBrandLink');

// Get a link definition
$definition = Links::get('ProductBrandLink');

// Check if link is enabled
Links::isLinkEnabled('ProductBrandLink');

// Get all enabled links
Links::enabled();

// Get all disabled links
Links::disabled();

// Get summary
Links::summary(); // ['total' => 5, 'enabled' => 4, 'disabled' => 1, 'booted' => 4]
```

## Model Requirements

For macros to work, models must use the `HasLinkMacros` trait:

```php
use Modules\Core\Concerns\HasLinkMacros;

class Product extends Model
{
    use HasLinkMacros;
}
```

This trait adds the `Macroable` trait and handles accessor/scope macros properly.
