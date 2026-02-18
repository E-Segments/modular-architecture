---
layout: docs
title: Blade Bridge
description: Auto-register views and Blade components
---

## Overview

The Blade Bridge automatically registers module views and Blade components, making them accessible via namespaced syntax.

## View Namespacing

Views in `resources/views/` are namespaced by module alias:

```
Modules/Blog/
└── resources/
    └── views/
        ├── index.blade.php
        ├── show.blade.php
        └── partials/
            └── card.blade.php
```

Usage:

```php
// In controllers
return view('blog::index');
return view('blog::show', ['post' => $post]);

// In Blade templates
@include('blog::partials.card', ['post' => $post])
```

## Class Components

Create components in `app/View/Components/`:

```
Modules/Blog/
└── app/
    └── View/
        └── Components/
            ├── PostCard.php
            └── CategoryBadge.php
```

### PostCard.php

```php
namespace Modules\Blog\View\Components;

use Illuminate\View\Component;

class PostCard extends Component
{
    public function __construct(
        public Post $post,
        public bool $showExcerpt = true
    ) {}

    public function render()
    {
        return view('blog::components.post-card');
    }
}
```

Usage in Blade:

```blade
<x-blog::post-card :post="$post" />
<x-blog::post-card :post="$post" :show-excerpt="false" />
```

## Anonymous Components

Create anonymous components in `resources/views/components/`:

```
Modules/Blog/
└── resources/
    └── views/
        └── components/
            ├── button.blade.php
            └── alert.blade.php
```

### button.blade.php

{% raw %}
```blade
@props(['type' => 'primary', 'size' => 'md'])

<button {{ $attributes->merge(['class' => "btn btn-{$type} btn-{$size}"]) }}>
    {{ $slot }}
</button>
```
{% endraw %}

Usage:

```blade
<x-blog::button type="secondary">Click Me</x-blog::button>
```

## Layouts

Define layouts in your module:

```
Modules/Blog/
└── resources/
    └── views/
        └── layouts/
            └── app.blade.php
```

### layouts/app.blade.php

```blade
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title') - Blog</title>
</head>
<body>
    @yield('content')
</body>
</html>
```

Usage:

```blade
@extends('blog::layouts.app')

@section('title', 'All Posts')

@section('content')
    <h1>Blog Posts</h1>
@endsection
```

## Component Prefixing

Components are prefixed with the module alias in kebab-case:

| Module | Component Class | Usage |
|--------|----------------|-------|
| Blog | `PostCard` | `<x-blog::post-card />` |
| ShoppingCart | `CartItem` | `<x-shopping-cart::cart-item />` |
| UserProfile | `Avatar` | `<x-user-profile::avatar />` |

## Configuration

```php
// config/modular.php
'bridges' => [
    'blade' => [
        'enabled' => true,
    ],
],
```

## Caching

In production, cache views:

```bash
php artisan view:cache
```

The bridge integrates with Laravel's view caching automatically.

## Best Practices

### 1. Use Namespaced Views

{% raw %}
```blade
{{-- Good --}}
@include('blog::partials.sidebar')

{{-- Avoid --}}
@include('modules.blog.partials.sidebar')
```
{% endraw %}

### 2. Component Slots

{% raw %}
```blade
{{-- resources/views/components/card.blade.php --}}
<div class="card">
    <div class="card-header">{{ $header }}</div>
    <div class="card-body">{{ $slot }}</div>
    @if(isset($footer))
        <div class="card-footer">{{ $footer }}</div>
    @endif
</div>
```
{% endraw %}

Usage:

```blade
<x-blog::card>
    <x-slot:header>Post Title</x-slot:header>
    
    Post content here...
    
    <x-slot:footer>
        <button>Read More</button>
    </x-slot:footer>
</x-blog::card>
```

### 3. Publishable Views

Allow users to customize views:

```php
// In your ServiceProvider
$this->publishes([
    module_path('Blog', 'resources/views') => resource_path('views/vendor/blog'),
], 'blog-views');
```
