---
layout: docs
title: Extension Points
description: Hook into module lifecycle events
---

## Overview

Extension points allow you to hook into module lifecycle events. They integrate with the `laravel-extensions` package for a type-safe hook system.

## Available Extension Points

### Before Hooks (Can Veto)

These fire before an action and can prevent it:

| Extension Point | Fires When |
|----------------|------------|
| `BeforeModuleInstall` | Before installing a module |
| `BeforeModuleEnable` | Before enabling a module |
| `BeforeModuleDisable` | Before disabling a module |
| `BeforeModuleUninstall` | Before removing a module |
| `BeforeModuleMigrate` | Before running migrations |
| `BeforeModuleSeed` | Before running seeders |

### After Hooks

These fire after an action completes:

| Extension Point | Fires When |
|----------------|------------|
| `ModuleInstalled` | After module is installed |
| `ModuleEnabled` | After module is enabled |
| `ModuleDisabled` | After module is disabled |
| `ModuleUninstalled` | After module is removed |
| `ModuleMigrated` | After migrations run |
| `ModuleSeeded` | After seeders run |

## Listening to Extension Points

### Register a Handler

```php
use Esegments\LaravelExtensions\Facades\Extensions;
use Esegments\ModularArchitecture\Extensions\Points\ModuleEnabled;

Extensions::register(
    ModuleEnabled::class,
    ClearCacheOnModuleChange::class
);
```

### Handler Implementation

```php
use Esegments\LaravelExtensions\Contracts\ExtensionHandlerContract;
use Esegments\ModularArchitecture\Extensions\Points\ModuleEnabled;

class ClearCacheOnModuleChange implements ExtensionHandlerContract
{
    public function handle(ModuleEnabled $extension): void
    {
        $module = $extension->module;
        
        Cache::tags(['modules', $module->getName()])->flush();
        
        Log::info("Cache cleared for module: {$module->getName()}");
    }
}
```

## Vetoing Actions

Before hooks can prevent the action by returning `false`:

```php
use Esegments\ModularArchitecture\Extensions\Points\BeforeModuleDisable;

class PreventCoreDisable implements ExtensionHandlerContract
{
    public function handle(BeforeModuleDisable $extension): bool
    {
        if ($extension->module->getName() === 'Core') {
            Log::warning('Attempted to disable Core module');
            return false; // Veto!
        }
        
        return true; // Allow
    }
}
```

Register with interruptible dispatch:

```php
Extensions::register(BeforeModuleDisable::class, PreventCoreDisable::class);

// The system uses dispatchInterruptible() internally
```

## Extension Point Properties

All extension points provide:

```php
$extension->module;      // Module instance
$extension->moduleName;  // Module name string
$extension->source;      // Source info (CLI, API, etc.)
$extension->version;     // Module version
$extension->path;        // Module path
```

## Common Use Cases

### 1. Clear Caches

```php
class ClearCachesHandler implements ExtensionHandlerContract
{
    public function handle(ModuleEnabled|ModuleDisabled $extension): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }
}

Extensions::register(ModuleEnabled::class, ClearCachesHandler::class);
Extensions::register(ModuleDisabled::class, ClearCachesHandler::class);
```

### 2. Audit Logging

```php
class AuditModuleChanges implements ExtensionHandlerContract
{
    public function handle(ExtensionPointContract $extension): void
    {
        AuditLog::create([
            'action' => class_basename($extension),
            'module' => $extension->moduleName,
            'user_id' => auth()->id(),
            'timestamp' => now(),
        ]);
    }
}

// Register for all module events
$events = [
    ModuleInstalled::class,
    ModuleEnabled::class,
    ModuleDisabled::class,
    ModuleUninstalled::class,
];

foreach ($events as $event) {
    Extensions::register($event, AuditModuleChanges::class);
}
```

### 3. Notify Administrators

```php
class NotifyOnModuleChange implements ExtensionHandlerContract
{
    public function handle(ExtensionPointContract $extension): void
    {
        $admins = User::where('is_admin', true)->get();
        
        Notification::send($admins, new ModuleChangedNotification(
            action: class_basename($extension),
            module: $extension->moduleName
        ));
    }
}
```

### 4. Pre-Migration Backup

```php
class BackupBeforeMigration implements ExtensionHandlerContract
{
    public function handle(BeforeModuleMigrate $extension): void
    {
        Artisan::call('backup:run', [
            '--only-db' => true,
            '--disable-notifications' => true,
        ]);
    }
}
```

### 5. Validation Before Enable

```php
class ValidateLicense implements ExtensionHandlerContract
{
    public function handle(BeforeModuleEnable $extension): bool
    {
        $module = $extension->module;
        
        if ($this->requiresLicense($module) && !$this->hasValidLicense($module)) {
            Log::error("Module {$module->getName()} requires a valid license");
            return false;
        }
        
        return true;
    }
}
```

## Extension Points Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                      Install Flow                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   BeforeModuleInstall ─┬─▶ [handlers] ──▶ Download/Extract │
│                        └─▶ (can veto)                       │
│                                   │                         │
│                                   ▼                         │
│                        ModuleInstalled ──▶ [handlers]       │
│                                                             │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                      Enable Flow                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   BeforeModuleEnable ─┬─▶ [handlers] ──▶ Update State      │
│                       └─▶ (can veto)                        │
│                                   │                         │
│                                   ▼                         │
│                        ModuleEnabled ──▶ [handlers]         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```
