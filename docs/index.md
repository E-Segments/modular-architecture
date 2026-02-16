---
layout: landing
---

<div class="text-center mb-16">
  <h1 class="text-5xl font-bold mb-6">Modular Architecture</h1>
  <p class="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-8">
    A powerful, flexible modular architecture system for Laravel applications. Build scalable, maintainable applications with independent, reusable modules.
  </p>
  <div class="flex gap-4 justify-center">
    <a href="/modular-architecture/docs/getting-started/" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
      Get Started
    </a>
    <a href="https://github.com/E-Segments/modular-architecture" class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
      View on GitHub
    </a>
  </div>
</div>

<div class="not-prose cards-grid mb-16">
  <div class="card">
    <div class="card-title">Module Registry</div>
    <div class="card-description">Centralized module management with dependency resolution and validation</div>
  </div>
  <div class="card">
    <div class="card-title">Framework Bridges</div>
    <div class="card-description">15 built-in bridges for routes, views, migrations, configs, and more</div>
  </div>
  <div class="card">
    <div class="card-title">Link Registry</div>
    <div class="card-description">Fluent API for cross-module relationships without tight coupling</div>
  </div>
  <div class="card">
    <div class="card-title">Generator Commands</div>
    <div class="card-description">20+ Artisan commands to scaffold modules, services, DTOs, and more</div>
  </div>
</div>

## Features

### Module System

- **Auto-discovery** - Modules are automatically discovered from the `Modules/` directory
- **Dependency Resolution** - Topological sorting ensures correct load order
- **Version Constraints** - Semver-compatible version validation
- **Enable/Disable** - Toggle modules without removing code

### Framework Bridges

| Bridge | Auto-discovers |
|--------|---------------|
| RouteBridge | `routes/*.php`, versioned APIs |
| BladeBridge | Views, components |
| MigrationBridge | Database migrations |
| ConfigBridge | Config files with deep merge |
| TranslationBridge | Lang files and JSON |
| CommandBridge | Artisan commands |
| ObserverBridge | Model observers |
| PolicyBridge | Model policies |
| ServiceBridge | Contract bindings |
| LivewireBridge | Livewire components |
| FilamentBridge | Filament resources |

### Link Registry

Define cross-module relationships without importing domain modules:

```php
Links::define('ProductBrandLink')
    ->requires('Products', 'Brands')
    ->belongsTo(Product::class, 'brand', Brand::class, 'brand_id')
    ->hasMany(Brand::class, 'products', Product::class, 'brand_id')
    ->relatedNameAccessor(Product::class, 'brandName', 'brand', 'name');
```

## Installation

```bash
composer require esegments/modular-architecture
```

```bash
php artisan vendor:publish --provider="Esegments\ModularArchitecture\ModularServiceProvider"
```

## Quick Start

Create your first module:

```bash
php artisan modular:make-module Products
```

This creates:

```
Modules/Products/
├── app/
│   ├── Models/
│   ├── Providers/ProductsServiceProvider.php
│   └── ...
├── database/
│   ├── migrations/
│   └── factories/
├── config/
├── routes/
├── resources/views/
├── composer.json
└── module.json
```

<div class="callout callout-info">
  <strong>Next Steps:</strong> Check out the <a href="/modular-architecture/docs/getting-started/">Getting Started guide</a> for a complete walkthrough.
</div>
