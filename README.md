# Modular Architecture for Laravel

A next-generation Laravel modular architecture package with semantic versioning, dependency management, and intelligent tooling.

## Features

- **Module Discovery** - Automatic discovery from multiple configurable paths
- **Dependency Management** - Semantic versioning with `composer/semver`
- **Circular Detection** - Detect and prevent circular dependencies
- **Load Order Resolution** - Topological sorting via `marcj/topsort`
- **Protected Modules** - Prevent critical modules from being disabled
- **Octane Compatible** - Static property caching for Laravel Octane
- **Beautiful CLI** - Interactive commands with Laravel Prompts
- **Validation** - Comprehensive module and dependency validation
- **Graph Visualization** - DOT and Mermaid format output

## Installation

```bash
composer require esegments/modular-architecture
```

Publish the configuration (optional):

```bash
php artisan vendor:publish --tag=modular-config
```

## Quick Start

### Create a Module

```bash
# Interactive mode
php artisan modular:make

# Direct mode
php artisan modular:make Blog --all

# With specific components
php artisan modular:make Products --model --controller --routes
```

### Module Structure

```
Modules/
└── Blog/
    ├── app/
    │   ├── Providers/
    │   │   └── BlogServiceProvider.php
    │   └── Models/
    │       └── Blog.php
    ├── database/
    │   ├── migrations/
    │   └── factories/
    ├── resources/
    │   └── views/
    ├── routes/
    │   └── web.php
    ├── composer.json
    └── module.json
```

### module.json

```json
{
    "name": "Blog",
    "alias": "blog",
    "version": "1.0.0",
    "description": "Blog module",
    "priority": 0,
    "providers": [
        "Modules\\Blog\\Providers\\BlogServiceProvider"
    ],
    "requires": {
        "Core": "^1.0"
    }
}
```

## Commands

| Command | Description |
|---------|-------------|
| `modular:make` | Create a new module |
| `modular:list` | List all modules |
| `modular:status` | Show module status and statistics |
| `modular:enable {name}` | Enable a module |
| `modular:disable {name}` | Disable a module |
| `modular:validate` | Validate module dependencies |
| `modular:dependents {name}` | Show modules depending on a module |
| `modular:graph` | Visualize dependency graph |
| `modular:remove {name}` | Remove a module |
| `modular:cache` | Build discovery cache |
| `modular:cache:clear` | Clear module caches |
| `modular:optimize` | Optimize for production |

## Usage

### Using the Facade

```php
use Esegments\ModularArchitecture\Facades\Modular;

// Get all modules
$modules = Modular::all();

// Get enabled modules
$enabled = Modular::enabled();

// Find a module
$blog = Modular::find('Blog');

// Check if module exists/enabled
Modular::exists('Blog');
Modular::isEnabled('Blog');

// Enable/disable
Modular::enable('Blog');
Modular::disable('Blog');

// Get dependents
$dependents = Modular::getDependents('Core');

// Validate
$result = Modular::validate('Blog');
if (!$result->isValid()) {
    foreach ($result->errors as $error) {
        echo $error;
    }
}
```

### Dependency Injection

```php
use Esegments\ModularArchitecture\Modular;

class SomeService
{
    public function __construct(
        protected Modular $modular,
    ) {}

    public function getActiveModules()
    {
        return $this->modular->enabled();
    }
}
```

## Configuration

```php
// config/modular.php

return [
    // Discovery paths
    'paths' => [
        base_path('Modules'),
        base_path('packages'),
    ],

    // Protected modules (cannot be disabled)
    'protected' => [
        'Core',
    ],

    // Cache settings
    'cache' => [
        'enabled' => env('MODULAR_CACHE', true),
        'ttl' => 86400,
    ],

    // Strict mode (fail on missing dependencies)
    'strict' => env('MODULAR_STRICT', false),
];
```

## Version Constraints

Uses `composer/semver` for version constraints:

| Constraint | Meaning |
|------------|---------|
| `^1.0` | >=1.0.0 <2.0.0 |
| `~1.5` | >=1.5.0 <2.0.0 |
| `>=2.0 <3.0` | Range |
| `1.0.*` | Any 1.0.x |
| `*` | Any version |

## Graph Visualization

```bash
# Text output
php artisan modular:graph

# DOT format (for Graphviz)
php artisan modular:graph --format=dot > graph.dot
dot -Tpng graph.dot > graph.png

# Mermaid format (for Markdown)
php artisan modular:graph --format=mermaid >> README.md
```

## Production Optimization

```bash
# Validate and build cache
php artisan modular:optimize

# Or manually
php artisan modular:validate --strict
php artisan modular:cache
```

## Testing

```bash
composer test
```

## License

MIT
