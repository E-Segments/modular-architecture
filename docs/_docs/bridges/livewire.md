---
layout: docs
title: Livewire Bridge
description: Auto-register Livewire components from modules
---

## Overview

The Livewire Bridge automatically discovers and registers Livewire components from your modules.

## Creating Components

Place Livewire components in `app/Livewire/`:

```
Modules/Blog/
└── app/
    └── Livewire/
        ├── PostTable.php
        ├── PostForm.php
        └── Comments/
            └── CommentList.php
```

### PostTable.php

```php
namespace Modules\Blog\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Blog\Models\Post;

class PostTable extends Component
{
    use WithPagination;

    public string $search = '';

    public function render()
    {
        return view('blog::livewire.post-table', [
            'posts' => Post::query()
                ->where('title', 'like', "%{$this->search}%")
                ->paginate(10),
        ]);
    }
}
```

## Using Components

Components are prefixed with the module name in kebab-case:

{% raw %}
```blade
{{-- Full component tag --}}
<livewire:blog::post-table />

{{-- With parameters --}}
<livewire:blog::post-form :post="$post" />

{{-- Nested components --}}
<livewire:blog::comments.comment-list :post-id="$post->id" />
```
{% endraw %}

## Component Naming

| Module | Class | Tag |
|--------|-------|-----|
| Blog | `PostTable` | `<livewire:blog::post-table />` |
| Blog | `Comments\CommentList` | `<livewire:blog::comments.comment-list />` |
| ShoppingCart | `CartWidget` | `<livewire:shopping-cart::cart-widget />` |

## Views

Component views go in `resources/views/livewire/`:

```
Modules/Blog/
└── resources/
    └── views/
        └── livewire/
            ├── post-table.blade.php
            └── comments/
                └── comment-list.blade.php
```

### post-table.blade.php

{% raw %}
```blade
<div>
    <input wire:model.live="search" placeholder="Search posts...">

    <table>
        @foreach($posts as $post)
            <tr>
                <td>{{ $post->title }}</td>
                <td>{{ $post->created_at->format('M d, Y') }}</td>
            </tr>
        @endforeach
    </table>

    {{ $posts->links() }}
</div>
```
{% endraw %}

## Full-Page Components

For full-page Livewire components:

```php
namespace Modules\Blog\Livewire;

use Livewire\Component;

class PostIndex extends Component
{
    public function render()
    {
        return view('blog::livewire.post-index')
            ->layout('blog::layouts.app');
    }
}
```

Route definition:

```php
// routes/web.php
Route::get('/posts', \Modules\Blog\Livewire\PostIndex::class)
    ->name('posts.index');
```

## Configuration

```php
// config/modular.php
'bridges' => [
    'livewire' => [
        'enabled' => true,
    ],
],
```

## Availability

The bridge only activates when Livewire is installed:

```php
public function isAvailable(): bool
{
    return class_exists(\Livewire\Component::class);
}
```

## Best Practices

### 1. Keep Components Focused

```php
// Good: Single responsibility
class PostTable extends Component { }
class PostForm extends Component { }
class PostFilters extends Component { }

// Avoid: Too much in one component
class PostManager extends Component { }
```

### 2. Use Events for Cross-Component Communication

```php
// In PostForm
$this->dispatch('post-created', postId: $post->id);

// In PostTable
#[On('post-created')]
public function refreshTable(): void
{
    // Table will re-render
}
```

### 3. Lazy Loading

```blade
<livewire:blog::heavy-component lazy />
```
