---
layout: docs
title: Link Definitions
description: Fluent API for defining cross-module relationships
---

## Creating a Link Definition

### Option 1: Fluent API

```php
use Esegments\ModularArchitecture\Facades\Links;

Links::define('TaxProductLink')
    ->requires('Tax', 'Products')
    ->belongsTo(Product::class, 'taxClass', TaxClass::class, 'tax_class_id')
    ->hasMany(TaxClass::class, 'products', Product::class, 'tax_class_id');
```

### Option 2: Link Definition Class

Create in `app/Links/` directory:

```php
namespace Modules\TaxProductLink\Links;

use Esegments\ModularArchitecture\Links\LinkDefinition;
use Modules\Products\Models\Product;
use Modules\Tax\Models\TaxClass;

class TaxProductLink extends LinkDefinition
{
    protected string $name = 'TaxProductLink';
    protected array $requires = ['Tax', 'Products'];

    public function define(): void
    {
        // Relationships
        $this->belongsTo(Product::class, 'taxClass', TaxClass::class, 'tax_class_id');
        $this->hasMany(TaxClass::class, 'products', Product::class, 'tax_class_id');
        
        // Macros
        $this->macro(Product::class, 'taxClassName', fn () => $this->taxClass?->name);
    }
}
```

## Relationship Methods

### belongsTo

```php
$link->belongsTo(
    Product::class,      // Model to add relationship to
    'taxClass',          // Relationship method name
    TaxClass::class,     // Related model
    'tax_class_id'       // Foreign key on Product
);
```

Usage:
```php
$product->taxClass; // Returns TaxClass|null
```

### hasMany

```php
$link->hasMany(
    TaxClass::class,     // Model to add relationship to
    'products',          // Relationship method name
    Product::class,      // Related model
    'tax_class_id'       // Foreign key on Product
);
```

Usage:
```php
$taxClass->products; // Returns Collection<Product>
```

### hasOne

```php
$link->hasOne(
    User::class,         // Model to add relationship to
    'profile',           // Relationship method name
    Profile::class,      // Related model
    'user_id'            // Foreign key on Profile
);
```

### belongsToMany

```php
$link->belongsToMany(
    Product::class,      // Model to add relationship to
    'categories',        // Relationship method name
    Category::class,     // Related model
    'category_product',  // Pivot table name
    'product_id',        // Foreign pivot key
    'category_id'        // Related pivot key
);
```

### morphTo / morphMany / morphToMany

```php
// Polymorphic relationships
$link->morphTo(Comment::class, 'commentable');
$link->morphMany(Post::class, 'comments', Comment::class, 'commentable');
```

## Adding Macros

Macros add methods to models:

### Accessor Macro

```php
$link->macro(Product::class, 'taxClassName', function () {
    return $this->taxClass?->name;
});
```

Usage:
```php
$product->taxClassName; // Returns string|null
```

### Scope Macro

```php
$link->macro(Product::class, 'scopeWithTax', function ($query) {
    return $query->whereNotNull('tax_class_id');
});

$link->macro(Product::class, 'scopeTaxable', function ($query) {
    return $query->whereHas('taxClass');
});
```

Usage:
```php
Product::withTax()->get();
Product::taxable()->get();
```

### Query Macro

```php
$link->macro(Product::class, 'forTaxClass', function (int $taxClassId) {
    return static::where('tax_class_id', $taxClassId)->get();
});
```

Usage:
```php
$products = Product::forTaxClass(1);
```

## Eager Loading

Configure default eager loads:

```php
$link->eagerLoad(ProductResource::class, 'table', ['taxClass']);
$link->eagerLoad(ProductResource::class, 'form', ['taxClass.rates']);
```

## Filament Integration

### Relation Manager

```php
$link->relationManager(
    ProductResource::class,
    TaxClassRelationManager::class
);
```

### Form Section

```php
$link->formSection(
    ProductResource::class,
    'tax',
    fn () => Section::make('Tax')->schema([
        Select::make('tax_class_id')
            ->relationship('taxClass', 'name')
    ])
);
```

### Table Column

```php
$link->tableColumn(
    ProductResource::class,
    TextColumn::make('taxClass.name')->label('Tax Class')
);
```

## Link Registration

Links are auto-discovered from `app/Links/` directories. Or register manually:

```php
// In a service provider
public function boot(): void
{
    Links::define('MyLink')
        ->requires('ModuleA', 'ModuleB')
        ->belongsTo(...);
}
```

## Link Lifecycle

1. **Discovery**: LinkBridge scans `app/Links/` directories
2. **Registration**: Link definitions are registered with LinkRegistry
3. **Activation Check**: Required modules must be enabled
4. **Boot**: Relationships and macros are applied

```php
// Check if link is active
if (Links::isEnabled('TaxProductLink')) {
    // Relationships are available
    $product->taxClass;
}
```

## Debugging Links

```bash
# List all links
php artisan modular:links

# Check link status
php artisan modular:links:status TaxProductLink
```

```php
// Programmatic debugging
$status = Links::status();
$summary = Links::summary();
```
