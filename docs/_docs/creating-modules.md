---
layout: docs
title: Creating Modules
description: Generate new modules with the Artisan command
---

## Interactive Mode

The easiest way to create a module:

```bash
php artisan modular:make
```

You'll be guided through:
1. Module name
2. Version number
3. Description
4. Components to include

## Direct Mode

Create a module directly:

```bash
php artisan modular:make Blog
```

### With Options

```bash
php artisan modular:make Blog \
    --ver=1.0.0 \
    --description="Blog management system" \
    --model \
    --controller \
    --views \
    --routes \
    --config \
    --tests
```

### Generate All Components

```bash
php artisan modular:make Blog --all
```

Creates: model, controller, views, routes, config, and tests.

## Generated Structure

```
Modules/Blog/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── BlogController.php
│   ├── Models/
│   │   └── Blog.php
│   └── Providers/
│       └── BlogServiceProvider.php
├── config/
│   └── config.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── views/
│       └── index.blade.php
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── composer.json
└── module.json
```

## Generator Commands

Beyond `modular:make`, use specific generators:

### Domain Classes

```bash
# Service class
php artisan modular:make-service OrderService Orders

# Repository class
php artisan modular:make-repository OrderRepository Orders

# DTO class
php artisan modular:make-dto CreateOrderData Orders

# Action class
php artisan modular:make-action CreateOrderAction Orders

# Enum
php artisan modular:make-enum OrderStatus Orders
```

### Laravel Classes

```bash
# Observer
php artisan modular:make-observer OrderObserver Orders

# Cast
php artisan modular:make-cast MoneyCast Orders

# Middleware
php artisan modular:make-middleware CheckOrderStatus Orders

# Exception
php artisan modular:make-exception OrderNotFoundException Orders

# Interface/Contract
php artisan modular:make-interface PaymentGatewayContract Orders
```

### Views & Components

```bash
# Blade view
php artisan modular:make-view orders/index Orders

# Blade component
php artisan modular:make-component OrderCard Orders

# Trait
php artisan modular:make-trait HasOrders Orders
```

## Laravel Command Overrides

When `commands.override` is enabled, Laravel's `make:*` commands support `--module`:

```bash
php artisan make:model Product --module=Products
php artisan make:controller ProductController --module=Products
php artisan make:migration create_products_table --module=Products
php artisan make:factory ProductFactory --module=Products
php artisan make:seeder ProductSeeder --module=Products
php artisan make:policy ProductPolicy --module=Products
php artisan make:request CreateProductRequest --module=Products
php artisan make:resource ProductResource --module=Products
php artisan make:test ProductTest --module=Products
php artisan make:event ProductCreated --module=Products
php artisan make:listener LogProductCreated --module=Products
php artisan make:job ProcessProduct --module=Products
php artisan make:mail ProductShipped --module=Products
php artisan make:notification ProductShippedNotification --module=Products
php artisan make:rule ValidSku --module=Products
```

## Customizing Stubs

Publish stubs for customization:

```bash
php artisan vendor:publish --tag=modular-stubs
```

Stubs are published to `stubs/modular/`:

```
stubs/modular/
├── action.stub
├── cast.stub
├── channel.stub
├── class.stub
├── command.stub
├── component.stub
├── controller.stub
├── dto.stub
├── enum.stub
├── exception.stub
├── helper.stub
├── interface.stub
├── middleware.stub
├── model.stub
├── module.json.stub
├── observer.stub
├── provider.stub
├── repository.stub
├── scope.stub
├── service.stub
├── trait.stub
└── view.stub
```

### Stub Variables

Available placeholders:

| Variable | Description |
|----------|-------------|
| `{% raw %}{{ namespace }}{% endraw %}` | Full namespace |
| `{% raw %}{{ class }}{% endraw %}` | Class name |
| `{% raw %}{{ module }}{% endraw %}` | Module name |
| `{% raw %}{{ module_lower }}{% endraw %}` | Module name (lowercase) |
| `{% raw %}{{ module_snake }}{% endraw %}` | Module name (snake_case) |

## Scaffolding Configuration

Configure defaults in `config/modular.php`:

```php
'scaffolding' => [
    'namespace' => 'Modules',
    'vendor' => 'YourCompany',
    'author_name' => 'Your Name',
    'author_email' => 'your@email.com',
],
```
