---
layout: docs
title: Filament Bridge
description: Auto-discover Filament resources, pages, and widgets
order: 34
parent: "Bridges"
---

## Overview

The Filament Bridge automatically discovers and registers Filament resources, pages, widgets, and relation managers from your modules.

## Directory Structure

```
Modules/Blog/
└── app/
    └── Filament/
        ├── Resources/
        │   ├── PostResource.php
        │   └── PostResource/
        │       ├── Pages/
        │       │   ├── CreatePost.php
        │       │   ├── EditPost.php
        │       │   └── ListPosts.php
        │       └── RelationManagers/
        │           └── CommentsRelationManager.php
        ├── Pages/
        │   └── BlogDashboard.php
        ├── Widgets/
        │   └── PostStatsWidget.php
        └── Clusters/
            └── BlogSettings.php
```

## Creating Resources

### PostResource.php

```php
namespace Modules\Blog\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Modules\Blog\Models\Post;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Blog';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255),
            Forms\Components\RichEditor::make('content')
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ])
                ->required(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\BadgeColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
```

## Custom Pages

```php
namespace Modules\Blog\Filament\Pages;

use Filament\Pages\Page;

class BlogDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationGroup = 'Blog';
    
    protected static string $view = 'blog::filament.pages.dashboard';
}
```

## Widgets

```php
namespace Modules\Blog\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Blog\Models\Post;

class PostStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Posts', Post::count()),
            Stat::make('Published', Post::where('status', 'published')->count()),
            Stat::make('Drafts', Post::where('status', 'draft')->count()),
        ];
    }
}
```

## Relation Managers

```php
namespace Modules\Blog\Filament\Resources\PostResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('author.name'),
                Tables\Columns\TextColumn::make('content')->limit(50),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ]);
    }
}
```

## Configuration

### Global

```php
// config/modular.php
'bridges' => [
    'filament' => [
        'enabled' => true,
    ],
],
```

### Per-Module

In `module.json`:

```json
{
    "extra": {
        "filament": {
            "navigation_group": "Content Management",
            "navigation_sort": 10
        }
    }
}
```

## Panel Registration

Resources are automatically registered with Filament panels. For specific panel targeting:

```php
class PostResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin();
    }
}
```

## Availability

The bridge activates when Filament is installed:

```php
public function isAvailable(): bool
{
    return class_exists(\Filament\FilamentServiceProvider::class);
}
```

## Discovered Components

The bridge discovers:

| Type | Location | Pattern |
|------|----------|---------|
| Resources | `app/Filament/Resources/` | `*Resource.php` |
| Pages | `app/Filament/Pages/` | `*.php` |
| Widgets | `app/Filament/Widgets/` | `*.php` |
| Clusters | `app/Filament/Clusters/` | `*.php` |
| RelationManagers | `Resources/*/RelationManagers/` | `*.php` |
