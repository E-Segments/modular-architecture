---
layout: docs
title: Other Bridges
description: Command, Middleware, Service, Config, Asset, Schedule, Migration, and Translation bridges
---

## Command Bridge

Auto-registers Artisan commands from modules.

### Directory Structure

```
Modules/Orders/
└── app/
    └── Console/
        └── Commands/
            └── ProcessPendingOrders.php
```

### Command Class

```php
namespace Modules\Orders\Console\Commands;

use Illuminate\Console\Command;

class ProcessPendingOrders extends Command
{
    protected $signature = 'orders:process-pending';
    protected $description = 'Process all pending orders';

    public function handle(): int
    {
        // ...
        return self::SUCCESS;
    }
}
```

Commands are automatically available via `php artisan`.

---

## Middleware Bridge

Auto-registers HTTP middleware with module-prefixed aliases.

### Directory Structure

```
Modules/Orders/
└── app/
    └── Http/
        └── Middleware/
            └── CheckOrderAccess.php
```

### Middleware Class

```php
namespace Modules\Orders\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckOrderAccess
{
    // Optional: define alias
    public static string $alias = 'order.access';
    
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()->canAccessOrders()) {
            abort(403);
        }
        return $next($request);
    }
}
```

### Usage

```php
// Auto-generated alias: orders.check-order-access
Route::middleware('orders.check-order-access')->group(function () {
    // ...
});

// Or with custom alias
Route::middleware('order.access')->group(function () {
    // ...
});
```

---

## Service Bridge

Auto-binds contracts to implementations.

### Directory Structure

```
Modules/Orders/
└── app/
    ├── Contracts/
    │   └── OrderServiceContract.php
    └── Services/
        └── OrderService.php
```

### Contract

```php
namespace Modules\Orders\Contracts;

interface OrderServiceContract
{
    public function create(array $data): Order;
    public function process(Order $order): void;
}
```

### Implementation

```php
namespace Modules\Orders\Services;

use Modules\Orders\Contracts\OrderServiceContract;

class OrderService implements OrderServiceContract
{
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function process(Order $order): void
    {
        // ...
    }
}
```

### Auto-Binding Rules

| Contract | Implementation | Binding |
|----------|---------------|---------|
| `OrderServiceContract` | `OrderService` | Bind |
| `*Repository` | - | Singleton |
| `*Manager` | - | Singleton |
| `*Factory` | - | Singleton |
| `*Service` | - | Regular bind |

---

## Config Bridge

Merges module configuration with deep merge support.

### Directory Structure

```
Modules/Orders/
└── config/
    ├── config.php
    ├── testing.php    # Environment-specific
    └── production.php
```

### config.php

```php
return [
    'default_status' => 'pending',
    'statuses' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
    ],
    'notifications' => [
        'email' => true,
        'sms' => false,
    ],
];
```

### Access

```php
config('orders.default_status');
config('orders.notifications.email');
```

### Environment Overrides

`config/testing.php`:
```php
return [
    'notifications' => [
        'email' => false, // Disable in tests
    ],
];
```

---

## Asset Bridge

Manages static assets from modules.

### Directory Structure

```
Modules/Blog/
├── public/
│   ├── css/
│   │   └── blog.css
│   └── js/
│       └── blog.js
└── resources/
    └── assets/
        └── images/
```

### Publishing Assets

```bash
php artisan modular:publish:assets
php artisan modular:publish:assets Blog
```

Published to: `public/modules/blog/`

### Symlinks (Development)

```php
'bridges' => [
    'assets' => [
        'enabled' => true,
        'symlink' => true, // Use symlinks in dev
        'auto_publish' => false,
    ],
],
```

### Helper Function

```php
$url = module_asset('blog', 'css/blog.css');
// Returns: /modules/blog/css/blog.css
```

In Blade:
```blade
<link rel="stylesheet" href="{{ module_asset('blog', 'css/blog.css') }}">
```

---

## Schedule Bridge

Auto-discovers scheduled tasks.

### Option 1: Schedule Class

```php
// Modules/Orders/app/Console/Schedule.php
namespace Modules\Orders\Console;

use Illuminate\Console\Scheduling\Schedule;

class Schedule
{
    public function __invoke(Schedule $schedule): void
    {
        $schedule->command('orders:process-pending')
            ->hourly();
            
        $schedule->command('orders:cleanup')
            ->daily()
            ->at('03:00');
    }
}
```

### Option 2: Kernel

```php
// Modules/Orders/app/Console/Kernel.php
namespace Modules\Orders\Console;

use Illuminate\Console\Scheduling\Schedule;

class Kernel
{
    public function schedule(Schedule $schedule): void
    {
        // Same as above
    }
}
```

---

## Migration Bridge

Auto-loads migrations from modules.

### Directory Structure

```
Modules/Orders/
└── database/
    └── migrations/
        ├── 2024_01_01_000000_create_orders_table.php
        └── 2024_01_02_000000_add_status_to_orders.php
```

Migrations are automatically included when running:
```bash
php artisan migrate
```

---

## Translation Bridge

Auto-loads language files.

### Directory Structure

```
Modules/Orders/
└── lang/
    ├── en/
    │   └── messages.php
    ├── es/
    │   └── messages.php
    └── en.json
```

### PHP Translations

```php
// lang/en/messages.php
return [
    'created' => 'Order created successfully',
    'shipped' => 'Your order has been shipped',
];
```

### JSON Translations

```json
// lang/en.json
{
    "Order created": "Order created",
    "Your order has been shipped": "Your order has been shipped"
}
```

### Usage

```php
trans('orders::messages.created');
__('orders::messages.shipped');
```

In Blade:
```blade
{{ __('orders::messages.created') }}
@lang('orders::messages.shipped')
```
