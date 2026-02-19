---
layout: docs
title: Event & Observer Bridges
description: Auto-register events, listeners, and observers
order: 35
parent: "Bridges"
---

## Event Bridge

Auto-discovers and registers event listeners and subscribers.

### Directory Structure

```
Modules/Orders/
└── app/
    ├── Events/
    │   ├── OrderPlaced.php
    │   └── OrderShipped.php
    ├── Listeners/
    │   ├── SendOrderConfirmation.php
    │   └── UpdateInventory.php
    └── Subscribers/
        └── OrderEventSubscriber.php
```

### Event Class

```php
namespace Modules\Orders\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order
    ) {}
}
```

### Listener Class

Listeners are auto-discovered by their type-hinted `handle` method:

```php
namespace Modules\Orders\Listeners;

use Modules\Orders\Events\OrderPlaced;

class SendOrderConfirmation
{
    public function handle(OrderPlaced $event): void
    {
        Mail::to($event->order->customer->email)
            ->send(new OrderConfirmationMail($event->order));
    }
}
```

### Event Subscriber

```php
namespace Modules\Orders\Subscribers;

use Illuminate\Events\Dispatcher;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Events\OrderShipped;

class OrderEventSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            OrderPlaced::class => 'handleOrderPlaced',
            OrderShipped::class => 'handleOrderShipped',
        ];
    }

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        // ...
    }

    public function handleOrderShipped(OrderShipped $event): void
    {
        // ...
    }
}
```

### Configuration

```php
'bridges' => [
    'events' => [
        'enabled' => true,
    ],
],
```

---

## Observer Bridge

Auto-registers model observers.

### Directory Structure

```
Modules/Orders/
└── app/
    └── Observers/
        └── OrderObserver.php
```

### Observer Class

Name convention: `{Model}Observer`

```php
namespace Modules\Orders\Observers;

use Modules\Orders\Models\Order;

class OrderObserver
{
    public function creating(Order $order): void
    {
        $order->order_number = $this->generateOrderNumber();
    }

    public function created(Order $order): void
    {
        event(new OrderPlaced($order));
    }

    public function updating(Order $order): void
    {
        if ($order->isDirty('status')) {
            // Status is changing
        }
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged('status')) {
            event(new OrderStatusChanged($order));
        }
    }

    public function deleted(Order $order): void
    {
        // Cleanup
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . now()->format('Ymd') . '-' . Str::random(6);
    }
}
```

### Model Resolution

The bridge finds the model by convention:

| Observer | Looks for Model |
|----------|----------------|
| `OrderObserver` | `Modules\Orders\Models\Order` |
| `ProductObserver` | `Modules\Products\Models\Product` |
| `UserProfileObserver` | `Modules\Users\Models\UserProfile` |

### Custom Model Binding

If convention doesn't work, specify the model:

```php
class OrderObserver
{
    public static string $model = \Modules\Orders\Models\Order::class;
    
    // ...
}
```

### Configuration

```php
'bridges' => [
    'observers' => [
        'enabled' => true,
    ],
],
```

---

## Policy Bridge

Auto-registers authorization policies.

### Directory Structure

```
Modules/Orders/
└── app/
    └── Policies/
        └── OrderPolicy.php
```

### Policy Class

Name convention: `{Model}Policy`

```php
namespace Modules\Orders\Policies;

use App\Models\User;
use Modules\Orders\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->customer_id
            || $user->hasPermission('orders.view-any');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->id === $order->customer_id
            && $order->status === 'pending';
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.delete');
    }
}
```

### Model Resolution

| Policy | Looks for Model |
|--------|----------------|
| `OrderPolicy` | `Modules\Orders\Models\Order` |
| `ProductPolicy` | `Modules\Products\Models\Product` |

### Usage

```php
// In controllers
$this->authorize('view', $order);
$this->authorize('create', Order::class);

// In Blade
@can('update', $order)
    <button>Edit</button>
@endcan

// Programmatic
if ($user->can('delete', $order)) {
    $order->delete();
}
```

### Configuration

```php
'bridges' => [
    'policies' => [
        'enabled' => true,
    ],
],
```
