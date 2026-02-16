<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Links;

use Closure;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

/**
 * Fluent builder for defining cross-module links.
 *
 * Replaces the boilerplate of ModuleLinkServiceProvider with a clean,
 * declarative API for defining relationships, macros, and Filament integrations.
 *
 * Example usage:
 * ```php
 * Links::define('ProductBrandLink')
 *     ->requires('Products', 'Brands')
 *     ->belongsTo(Product::class, 'brand', Brand::class, 'brand_id')
 *     ->hasMany(Brand::class, 'products', Product::class, 'brand_id')
 *     ->relatedNameAccessor(Product::class, 'brandName', 'brand', 'name')
 *     ->hasRelationMacro(Product::class, 'hasBrand', 'brand_id')
 *     ->filterScope(Product::class, 'scopeOfBrand', 'brand_id')
 *     ->productCountMacro(Brand::class)
 *     ->eagerLoad(BrandResource::class, 'table', ['products']);
 * ```
 */
class LinkDefinition
{
    /**
     * The unique name of this link.
     */
    protected string $name;

    /**
     * Required module names for this link to activate.
     *
     * @var array<string>
     */
    protected array $requiredModules = [];

    /**
     * Relationship definitions to register.
     *
     * @var array<array{type: string, model: class-string, relation: string, related: class-string, options: array<string, mixed>}>
     */
    protected array $relationships = [];

    /**
     * Macro definitions to register.
     *
     * @var array<array{type: string, model: class-string, name: string, options: array<string, mixed>}>
     */
    protected array $macros = [];

    /**
     * Resource configurations to apply.
     *
     * @var array<class-string, array{eagerLoad: array<string, array<string>>, withCount: array<string>, tableColumns: array<array{name: string, callback: Closure, priority: int}>, formComponents: array<Closure>}>
     */
    protected array $resourceConfigs = [];

    /**
     * RelationManagers to register.
     *
     * @var array<array{resource: class-string, manager: class-string}>
     */
    protected array $relationManagers = [];

    /**
     * Form sections to register.
     *
     * @var array<array{resource: class-string, callback: Closure}>
     */
    protected array $formSections = [];

    /**
     * Livewire components to register.
     *
     * @var array<string, class-string>
     */
    protected array $livewireComponents = [];

    /**
     * Custom boot callbacks.
     *
     * @var array<Closure>
     */
    protected array $bootCallbacks = [];

    /**
     * Whether this link has been booted.
     */
    protected bool $booted = false;

    /**
     * Optional registry instances for Filament integrations.
     *
     * @var array<string, object>
     */
    protected array $registries = [];

    /**
     * Create a new link definition.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the link name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get required modules.
     *
     * @return array<string>
     */
    public function getRequiredModules(): array
    {
        return $this->requiredModules;
    }

    /**
     * Check if this link has been booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Set registry instances for Filament integrations.
     *
     * @param  array<string, object>  $registries
     */
    public function setRegistries(array $registries): self
    {
        $this->registries = $registries;

        return $this;
    }

    // =========================================================================
    // MODULE REQUIREMENTS
    // =========================================================================

    /**
     * Define required modules for this link.
     *
     * @param  string  ...$modules  Module names required for this link
     */
    public function requires(string ...$modules): self
    {
        $this->requiredModules = array_merge($this->requiredModules, $modules);

        return $this;
    }

    // =========================================================================
    // RELATIONSHIP DEFINITIONS
    // =========================================================================

    /**
     * Add a belongsTo relationship.
     *
     * @param  class-string  $modelClass  The model to add the relationship to
     * @param  string  $relationName  The relationship method name
     * @param  class-string  $relatedClass  The related model class
     * @param  string|null  $foreignKey  The foreign key column
     * @param  string|null  $ownerKey  The owner key column
     */
    public function belongsTo(
        string $modelClass,
        string $relationName,
        string $relatedClass,
        ?string $foreignKey = null,
        ?string $ownerKey = null
    ): self {
        $this->relationships[] = [
            'type' => 'belongsTo',
            'model' => $modelClass,
            'relation' => $relationName,
            'related' => $relatedClass,
            'options' => [
                'foreignKey' => $foreignKey,
                'ownerKey' => $ownerKey,
            ],
        ];

        return $this;
    }

    /**
     * Add a hasMany relationship.
     *
     * @param  class-string  $modelClass  The model to add the relationship to
     * @param  string  $relationName  The relationship method name
     * @param  class-string  $relatedClass  The related model class
     * @param  string|null  $foreignKey  The foreign key column
     * @param  string|null  $localKey  The local key column
     */
    public function hasMany(
        string $modelClass,
        string $relationName,
        string $relatedClass,
        ?string $foreignKey = null,
        ?string $localKey = null
    ): self {
        $this->relationships[] = [
            'type' => 'hasMany',
            'model' => $modelClass,
            'relation' => $relationName,
            'related' => $relatedClass,
            'options' => [
                'foreignKey' => $foreignKey,
                'localKey' => $localKey,
            ],
        ];

        return $this;
    }

    /**
     * Add a hasOne relationship.
     *
     * @param  class-string  $modelClass  The model to add the relationship to
     * @param  string  $relationName  The relationship method name
     * @param  class-string  $relatedClass  The related model class
     * @param  string|null  $foreignKey  The foreign key column
     * @param  string|null  $localKey  The local key column
     */
    public function hasOne(
        string $modelClass,
        string $relationName,
        string $relatedClass,
        ?string $foreignKey = null,
        ?string $localKey = null
    ): self {
        $this->relationships[] = [
            'type' => 'hasOne',
            'model' => $modelClass,
            'relation' => $relationName,
            'related' => $relatedClass,
            'options' => [
                'foreignKey' => $foreignKey,
                'localKey' => $localKey,
            ],
        ];

        return $this;
    }

    /**
     * Add a belongsToMany relationship.
     *
     * @param  class-string  $modelClass  The model to add the relationship to
     * @param  string  $relationName  The relationship method name
     * @param  class-string  $relatedClass  The related model class
     * @param  string|null  $table  The pivot table name
     * @param  string|null  $foreignPivotKey  The foreign pivot key
     * @param  string|null  $relatedPivotKey  The related pivot key
     * @param  array<string>  $pivotColumns  Additional pivot columns
     * @param  bool  $withTimestamps  Whether to include timestamps on pivot
     */
    public function belongsToMany(
        string $modelClass,
        string $relationName,
        string $relatedClass,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        array $pivotColumns = [],
        bool $withTimestamps = true
    ): self {
        $this->relationships[] = [
            'type' => 'belongsToMany',
            'model' => $modelClass,
            'relation' => $relationName,
            'related' => $relatedClass,
            'options' => [
                'table' => $table,
                'foreignPivotKey' => $foreignPivotKey,
                'relatedPivotKey' => $relatedPivotKey,
                'pivotColumns' => $pivotColumns,
                'withTimestamps' => $withTimestamps,
            ],
        ];

        return $this;
    }

    /**
     * Add a morphToMany relationship.
     *
     * @param  class-string  $modelClass  The model to add the relationship to
     * @param  string  $relationName  The relationship method name
     * @param  class-string  $relatedClass  The related model class
     * @param  string  $morphName  The morph name
     * @param  string|null  $table  The pivot table name
     * @param  array<string>  $pivotColumns  Additional pivot columns
     * @param  bool  $withTimestamps  Whether to include timestamps on pivot
     */
    public function morphToMany(
        string $modelClass,
        string $relationName,
        string $relatedClass,
        string $morphName,
        ?string $table = null,
        array $pivotColumns = [],
        bool $withTimestamps = true
    ): self {
        $this->relationships[] = [
            'type' => 'morphToMany',
            'model' => $modelClass,
            'relation' => $relationName,
            'related' => $relatedClass,
            'options' => [
                'morphName' => $morphName,
                'table' => $table,
                'pivotColumns' => $pivotColumns,
                'withTimestamps' => $withTimestamps,
            ],
        ];

        return $this;
    }

    /**
     * Add a morphedByMany relationship.
     *
     * @param  class-string  $modelClass  The model to add the relationship to
     * @param  string  $relationName  The relationship method name
     * @param  class-string  $relatedClass  The related model class
     * @param  string  $morphName  The morph name
     * @param  string|null  $table  The pivot table name
     */
    public function morphedByMany(
        string $modelClass,
        string $relationName,
        string $relatedClass,
        string $morphName,
        ?string $table = null
    ): self {
        $this->relationships[] = [
            'type' => 'morphedByMany',
            'model' => $modelClass,
            'relation' => $relationName,
            'related' => $relatedClass,
            'options' => [
                'morphName' => $morphName,
                'table' => $table,
            ],
        ];

        return $this;
    }

    // =========================================================================
    // MACRO DEFINITIONS
    // =========================================================================

    /**
     * Add a related attribute accessor macro.
     *
     * Creates: $product->brandName() returns brand name or null
     *
     * @param  class-string  $modelClass  The model to add the macro to
     * @param  string  $macroName  The macro method name
     * @param  string  $relationName  The relation to access
     * @param  string  $attributeName  The attribute to return
     */
    public function relatedNameAccessor(
        string $modelClass,
        string $macroName,
        string $relationName,
        string $attributeName = 'name'
    ): self {
        $this->macros[] = [
            'type' => 'relatedAttributeAccessor',
            'model' => $modelClass,
            'name' => $macroName,
            'options' => [
                'relationName' => $relationName,
                'attributeName' => $attributeName,
            ],
        ];

        return $this;
    }

    /**
     * Add a related names accessor macro (comma-separated).
     *
     * Creates: $product->categoryNames() returns "Category A, Category B"
     *
     * @param  class-string  $modelClass  The model to add the macro to
     * @param  string  $macroName  The macro method name
     * @param  string  $relationName  The relation to access
     * @param  string  $nameColumn  The column to pluck
     */
    public function relatedNamesAccessor(
        string $modelClass,
        string $macroName,
        string $relationName,
        string $nameColumn = 'name'
    ): self {
        $this->macros[] = [
            'type' => 'relatedNamesAccessor',
            'model' => $modelClass,
            'name' => $macroName,
            'options' => [
                'relationName' => $relationName,
                'nameColumn' => $nameColumn,
            ],
        ];

        return $this;
    }

    /**
     * Add a "has relation" check macro.
     *
     * Creates: $product->hasBrand() returns true/false
     *
     * @param  class-string  $modelClass  The model to add the macro to
     * @param  string  $macroName  The macro method name
     * @param  string  $foreignKey  The foreign key to check
     */
    public function hasRelationMacro(
        string $modelClass,
        string $macroName,
        string $foreignKey
    ): self {
        $this->macros[] = [
            'type' => 'hasRelationMacro',
            'model' => $modelClass,
            'name' => $macroName,
            'options' => [
                'foreignKey' => $foreignKey,
            ],
        ];

        return $this;
    }

    /**
     * Add a filter scope by foreign key.
     *
     * Creates: Product::ofBrand($id)
     *
     * @param  class-string  $modelClass  The model to add the scope to
     * @param  string  $scopeName  The scope method name (e.g., 'scopeOfBrand')
     * @param  string  $foreignKey  The foreign key column
     */
    public function filterScope(
        string $modelClass,
        string $scopeName,
        string $foreignKey
    ): self {
        $this->macros[] = [
            'type' => 'entityFilterScope',
            'model' => $modelClass,
            'name' => $scopeName,
            'options' => [
                'foreignKey' => $foreignKey,
                'relationName' => null,
                'relatedTable' => null,
            ],
        ];

        return $this;
    }

    /**
     * Add a filter scope by relation (whereHas).
     *
     * Creates: Product::inCategory($id)
     *
     * @param  class-string  $modelClass  The model to add the scope to
     * @param  string  $scopeName  The scope method name
     * @param  string  $relationName  The relation name for whereHas
     * @param  string  $relatedTable  The related table name
     */
    public function filterScopeViaRelation(
        string $modelClass,
        string $scopeName,
        string $relationName,
        string $relatedTable
    ): self {
        $this->macros[] = [
            'type' => 'entityFilterScope',
            'model' => $modelClass,
            'name' => $scopeName,
            'options' => [
                'foreignKey' => null,
                'relationName' => $relationName,
                'relatedTable' => $relatedTable,
            ],
        ];

        return $this;
    }

    /**
     * Add a slug filter scope.
     *
     * Creates: Product::ofBrandSlug($slug)
     *
     * @param  class-string  $modelClass  The model to add the scope to
     * @param  string  $scopeName  The scope method name
     * @param  string  $relationName  The relation name
     * @param  string  $slugColumn  The slug column on related table
     */
    public function slugFilterScope(
        string $modelClass,
        string $scopeName,
        string $relationName,
        string $slugColumn = 'slug'
    ): self {
        $this->macros[] = [
            'type' => 'entitySlugFilterScope',
            'model' => $modelClass,
            'name' => $scopeName,
            'options' => [
                'relationName' => $relationName,
                'slugColumn' => $slugColumn,
            ],
        ];

        return $this;
    }

    /**
     * Add a product count macro.
     *
     * Creates: $brand->product_count
     *
     * @param  class-string  $modelClass  The model to add the macro to
     * @param  string  $relationName  The products relation name
     */
    public function productCountMacro(
        string $modelClass,
        string $relationName = 'products'
    ): self {
        $this->macros[] = [
            'type' => 'productCountMacro',
            'model' => $modelClass,
            'name' => 'getProductCountAttribute',
            'options' => [
                'relationName' => $relationName,
            ],
        ];

        return $this;
    }

    /**
     * Add an active product count macro.
     *
     * Creates: $brand->active_product_count
     *
     * @param  class-string  $modelClass  The model to add the macro to
     * @param  string  $relationName  The products relation name
     * @param  string  $activeColumn  The column to check for active status
     */
    public function activeProductCountMacro(
        string $modelClass,
        string $relationName = 'products',
        string $activeColumn = 'is_active'
    ): self {
        $this->macros[] = [
            'type' => 'activeProductCountMacro',
            'model' => $modelClass,
            'name' => 'getActiveProductCountAttribute',
            'options' => [
                'relationName' => $relationName,
                'activeColumn' => $activeColumn,
            ],
        ];

        return $this;
    }

    /**
     * Add a "with relation" scope.
     *
     * Creates: Category::withProducts()
     *
     * @param  class-string  $modelClass  The model to add the scope to
     * @param  string  $scopeName  The scope method name
     * @param  string  $relationName  The relation name
     */
    public function withRelationScope(
        string $modelClass,
        string $scopeName,
        string $relationName
    ): self {
        $this->macros[] = [
            'type' => 'withRelationScope',
            'model' => $modelClass,
            'name' => $scopeName,
            'options' => [
                'relationName' => $relationName,
            ],
        ];

        return $this;
    }

    /**
     * Add a primary relation macro.
     *
     * Creates: $product->primaryCategory() returns first category or null
     *
     * @param  class-string  $modelClass  The model to add the macro to
     * @param  string  $macroName  The macro method name
     * @param  string  $relationName  The relation name
     */
    public function primaryRelationMacro(
        string $modelClass,
        string $macroName,
        string $relationName
    ): self {
        $this->macros[] = [
            'type' => 'primaryRelationMacro',
            'model' => $modelClass,
            'name' => $macroName,
            'options' => [
                'relationName' => $relationName,
            ],
        ];

        return $this;
    }

    /**
     * Add a custom macro.
     *
     * @param  class-string  $modelClass  The model to add the macro to
     * @param  string  $macroName  The macro method name
     * @param  Closure  $callback  The macro implementation
     */
    public function customMacro(
        string $modelClass,
        string $macroName,
        Closure $callback
    ): self {
        $this->macros[] = [
            'type' => 'custom',
            'model' => $modelClass,
            'name' => $macroName,
            'options' => [
                'callback' => $callback,
            ],
        ];

        return $this;
    }

    // =========================================================================
    // FILAMENT INTEGRATIONS
    // =========================================================================

    /**
     * Add eager loading for a Filament resource.
     *
     * @param  class-string  $resourceClass  The Filament Resource class
     * @param  'table'|'form'|'view'|'all'  $context  The context
     * @param  array<string>  $relations  Relations to eager load
     */
    public function eagerLoad(
        string $resourceClass,
        string $context,
        array $relations
    ): self {
        if (! isset($this->resourceConfigs[$resourceClass])) {
            $this->resourceConfigs[$resourceClass] = [
                'eagerLoad' => [],
                'withCount' => [],
                'tableColumns' => [],
                'formComponents' => [],
            ];
        }

        if ($context === 'all') {
            foreach (['table', 'form', 'view'] as $ctx) {
                $this->resourceConfigs[$resourceClass]['eagerLoad'][$ctx] = array_merge(
                    $this->resourceConfigs[$resourceClass]['eagerLoad'][$ctx] ?? [],
                    $relations
                );
            }
        } else {
            $this->resourceConfigs[$resourceClass]['eagerLoad'][$context] = array_merge(
                $this->resourceConfigs[$resourceClass]['eagerLoad'][$context] ?? [],
                $relations
            );
        }

        return $this;
    }

    /**
     * Add withCount for a Filament resource.
     *
     * @param  class-string  $resourceClass  The Filament Resource class
     * @param  array<string>  $relations  Relations to count
     */
    public function withCount(string $resourceClass, array $relations): self
    {
        if (! isset($this->resourceConfigs[$resourceClass])) {
            $this->resourceConfigs[$resourceClass] = [
                'eagerLoad' => [],
                'withCount' => [],
                'tableColumns' => [],
                'formComponents' => [],
            ];
        }

        $this->resourceConfigs[$resourceClass]['withCount'] = array_merge(
            $this->resourceConfigs[$resourceClass]['withCount'],
            $relations
        );

        return $this;
    }

    /**
     * Add a table column to a Filament resource.
     *
     * @param  class-string  $resourceClass  The Filament Resource class
     * @param  string  $name  Unique identifier for this column
     * @param  Closure(): Column  $callback  Callback that returns the column
     * @param  int  $priority  Sort priority (lower = first)
     */
    public function tableColumn(
        string $resourceClass,
        string $name,
        Closure $callback,
        int $priority = 100
    ): self {
        if (! isset($this->resourceConfigs[$resourceClass])) {
            $this->resourceConfigs[$resourceClass] = [
                'eagerLoad' => [],
                'withCount' => [],
                'tableColumns' => [],
                'formComponents' => [],
            ];
        }

        $this->resourceConfigs[$resourceClass]['tableColumns'][] = [
            'name' => $name,
            'callback' => $callback,
            'priority' => $priority,
        ];

        return $this;
    }

    /**
     * Register a RelationManager for a Filament resource.
     *
     * @param  class-string  $resourceClass  The Filament Resource class
     * @param  class-string  $managerClass  The RelationManager class
     */
    public function relationManager(string $resourceClass, string $managerClass): self
    {
        $this->relationManagers[] = [
            'resource' => $resourceClass,
            'manager' => $managerClass,
        ];

        return $this;
    }

    /**
     * Register a form section for a Filament resource.
     *
     * @param  class-string  $resourceClass  The Filament Resource class
     * @param  Closure  $callback  Callback that returns form components
     */
    public function formSection(string $resourceClass, Closure $callback): self
    {
        $this->formSections[] = [
            'resource' => $resourceClass,
            'callback' => $callback,
        ];

        return $this;
    }

    // =========================================================================
    // LIVEWIRE COMPONENTS
    // =========================================================================

    /**
     * Register a Livewire component.
     *
     * @param  string  $alias  The Livewire component alias
     * @param  class-string  $componentClass  The component class
     */
    public function livewireComponent(string $alias, string $componentClass): self
    {
        $this->livewireComponents[$alias] = $componentClass;

        return $this;
    }

    // =========================================================================
    // CUSTOM BOOT CALLBACKS
    // =========================================================================

    /**
     * Add a custom boot callback.
     *
     * @param  Closure  $callback  The callback to execute on boot
     */
    public function onBoot(Closure $callback): self
    {
        $this->bootCallbacks[] = $callback;

        return $this;
    }

    // =========================================================================
    // BOOT METHOD
    // =========================================================================

    /**
     * Boot this link definition.
     *
     * Registers all relationships, macros, and integrations.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->bootRelationships();
        $this->bootMacros();
        $this->bootResourceConfigs();
        $this->bootRelationManagers();
        $this->bootFormSections();
        $this->bootLivewireComponents();
        $this->bootCustomCallbacks();

        $this->booted = true;
    }

    /**
     * Boot relationship definitions.
     */
    protected function bootRelationships(): void
    {
        foreach ($this->relationships as $rel) {
            $modelClass = $rel['model'];
            $relationName = $rel['relation'];
            $relatedClass = $rel['related'];
            $options = $rel['options'];

            match ($rel['type']) {
                'belongsTo' => $modelClass::resolveRelationUsing(
                    $relationName,
                    static fn ($model) => $model->belongsTo(
                        $relatedClass,
                        $options['foreignKey'],
                        $options['ownerKey']
                    )
                ),
                'hasMany' => $modelClass::resolveRelationUsing(
                    $relationName,
                    static fn ($model) => $model->hasMany(
                        $relatedClass,
                        $options['foreignKey'],
                        $options['localKey']
                    )
                ),
                'hasOne' => $modelClass::resolveRelationUsing(
                    $relationName,
                    static fn ($model) => $model->hasOne(
                        $relatedClass,
                        $options['foreignKey'],
                        $options['localKey']
                    )
                ),
                'belongsToMany' => $modelClass::resolveRelationUsing(
                    $relationName,
                    static function ($model) use ($relatedClass, $options) {
                        $relation = $model->belongsToMany(
                            $relatedClass,
                            $options['table'],
                            $options['foreignPivotKey'],
                            $options['relatedPivotKey']
                        );

                        if (! empty($options['pivotColumns'])) {
                            $relation->withPivot($options['pivotColumns']);
                        }

                        if ($options['withTimestamps']) {
                            $relation->withTimestamps();
                        }

                        return $relation;
                    }
                ),
                'morphToMany' => $modelClass::resolveRelationUsing(
                    $relationName,
                    static function ($model) use ($relatedClass, $options) {
                        $relation = $model->morphToMany(
                            $relatedClass,
                            $options['morphName'],
                            $options['table']
                        );

                        if (! empty($options['pivotColumns'])) {
                            $relation->withPivot($options['pivotColumns']);
                        }

                        if ($options['withTimestamps']) {
                            $relation->withTimestamps();
                        }

                        return $relation;
                    }
                ),
                'morphedByMany' => $modelClass::resolveRelationUsing(
                    $relationName,
                    static fn ($model) => $model->morphedByMany(
                        $relatedClass,
                        $options['morphName'],
                        $options['table']
                    )
                ),
                default => null,
            };
        }
    }

    /**
     * Boot macro definitions.
     */
    protected function bootMacros(): void
    {
        foreach ($this->macros as $macro) {
            $modelClass = $macro['model'];
            $macroName = $macro['name'];
            $options = $macro['options'];

            match ($macro['type']) {
                'relatedAttributeAccessor' => $this->bootRelatedAttributeAccessor(
                    $modelClass,
                    $macroName,
                    $options['relationName'],
                    $options['attributeName']
                ),
                'relatedNamesAccessor' => $this->bootRelatedNamesAccessor(
                    $modelClass,
                    $macroName,
                    $options['relationName'],
                    $options['nameColumn']
                ),
                'hasRelationMacro' => $this->bootHasRelationMacro(
                    $modelClass,
                    $macroName,
                    $options['foreignKey']
                ),
                'entityFilterScope' => $this->bootEntityFilterScope(
                    $modelClass,
                    $macroName,
                    $options['foreignKey'],
                    $options['relationName'],
                    $options['relatedTable']
                ),
                'entitySlugFilterScope' => $this->bootEntitySlugFilterScope(
                    $modelClass,
                    $macroName,
                    $options['relationName'],
                    $options['slugColumn']
                ),
                'productCountMacro' => $this->bootProductCountMacro(
                    $modelClass,
                    $options['relationName']
                ),
                'activeProductCountMacro' => $this->bootActiveProductCountMacro(
                    $modelClass,
                    $options['relationName'],
                    $options['activeColumn']
                ),
                'withRelationScope' => $this->bootWithRelationScope(
                    $modelClass,
                    $macroName,
                    $options['relationName']
                ),
                'primaryRelationMacro' => $this->bootPrimaryRelationMacro(
                    $modelClass,
                    $macroName,
                    $options['relationName']
                ),
                'custom' => $modelClass::macro($macroName, $options['callback']),
                default => null,
            };
        }
    }

    /**
     * Boot related attribute accessor macro.
     */
    protected function bootRelatedAttributeAccessor(
        string $modelClass,
        string $macroName,
        string $relationName,
        string $attributeName
    ): void {
        $modelClass::macro($macroName, function () use ($relationName, $attributeName): mixed {
            if (! method_exists($this, $relationName)) {
                return null;
            }

            /** @phpstan-ignore property.dynamicName */
            $related = $this->{$relationName};

            /** @phpstan-ignore property.dynamicName */
            return $related?->{$attributeName};
        });
    }

    /**
     * Boot related names accessor macro.
     */
    protected function bootRelatedNamesAccessor(
        string $modelClass,
        string $macroName,
        string $relationName,
        string $nameColumn
    ): void {
        $modelClass::macro($macroName, function () use ($relationName, $nameColumn): string {
            if (! method_exists($this, $relationName)) {
                return '';
            }

            /** @phpstan-ignore-next-line */
            if (! $this->relationLoaded($relationName)) {
                /** @phpstan-ignore-next-line */
                $this->load($relationName);
            }

            /** @phpstan-ignore-next-line */
            return $this->getRelation($relationName)->pluck($nameColumn)->implode(', ');
        });
    }

    /**
     * Boot has relation macro.
     */
    protected function bootHasRelationMacro(
        string $modelClass,
        string $macroName,
        string $foreignKey
    ): void {
        $modelClass::macro($macroName, function () use ($foreignKey): bool {
            /** @phpstan-ignore property.dynamicName */
            return $this->{$foreignKey} !== null;
        });
    }

    /**
     * Boot entity filter scope.
     */
    protected function bootEntityFilterScope(
        string $modelClass,
        string $scopeName,
        ?string $foreignKey,
        ?string $relationName,
        ?string $relatedTable
    ): void {
        if ($foreignKey !== null) {
            /** @phpstan-ignore-next-line */
            $modelClass::macro($scopeName, static function (Builder $query, int $id) use ($foreignKey): Builder {
                return $query->where($foreignKey, $id);
            });
        } elseif ($relationName !== null && $relatedTable !== null) {
            /** @phpstan-ignore-next-line */
            $modelClass::macro($scopeName, static function (Builder $query, int $id) use ($relationName, $relatedTable): Builder {
                return $query->whereHas($relationName, function (Builder $q) use ($relatedTable, $id): void {
                    $q->where("$relatedTable.id", $id);
                });
            });
        }
    }

    /**
     * Boot entity slug filter scope.
     */
    protected function bootEntitySlugFilterScope(
        string $modelClass,
        string $scopeName,
        string $relationName,
        string $slugColumn
    ): void {
        /** @phpstan-ignore-next-line */
        $modelClass::macro($scopeName, static function (Builder $query, string $slug) use ($relationName, $slugColumn): Builder {
            return $query->whereHas($relationName, function (Builder $q) use ($slugColumn, $slug): void {
                $q->where($slugColumn, $slug);
            });
        });
    }

    /**
     * Boot product count macro.
     */
    protected function bootProductCountMacro(string $modelClass, string $relationName): void
    {
        $modelClass::macro('getProductCountAttribute', function () use ($relationName): int {
            $countAttribute = $relationName.'_count';
            /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
            if (isset($this->attributes[$countAttribute])) {
                /** @phpstan-ignore offsetAccess.nonOffsetAccessible, cast.int */
                return (int) $this->attributes[$countAttribute];
            }

            /** @phpstan-ignore-next-line */
            if ($this->relationLoaded($relationName)) {
                /** @phpstan-ignore-next-line */
                return $this->getRelation($relationName)->count();
            }

            if (! method_exists($this, $relationName)) {
                return 0;
            }

            /** @phpstan-ignore-next-line */
            return $this->{$relationName}()->count();
        });
    }

    /**
     * Boot active product count macro.
     */
    protected function bootActiveProductCountMacro(
        string $modelClass,
        string $relationName,
        string $activeColumn
    ): void {
        $modelClass::macro('getActiveProductCountAttribute', function () use ($relationName, $activeColumn): int {
            $countAttribute = 'active_'.$relationName.'_count';
            /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
            if (isset($this->attributes[$countAttribute])) {
                /** @phpstan-ignore offsetAccess.nonOffsetAccessible, cast.int */
                return (int) $this->attributes[$countAttribute];
            }

            /** @phpstan-ignore-next-line */
            if ($this->relationLoaded($relationName)) {
                /** @phpstan-ignore-next-line */
                return $this->getRelation($relationName)->where($activeColumn, true)->count();
            }

            if (! method_exists($this, $relationName)) {
                return 0;
            }

            /** @phpstan-ignore-next-line */
            return $this->{$relationName}()->where($activeColumn, true)->count();
        });
    }

    /**
     * Boot with relation scope.
     */
    protected function bootWithRelationScope(
        string $modelClass,
        string $scopeName,
        string $relationName
    ): void {
        /** @phpstan-ignore-next-line */
        $modelClass::macro($scopeName, static function (Builder $query) use ($relationName): Builder {
            return $query->whereHas($relationName);
        });
    }

    /**
     * Boot primary relation macro.
     */
    protected function bootPrimaryRelationMacro(
        string $modelClass,
        string $macroName,
        string $relationName
    ): void {
        $modelClass::macro($macroName, function () use ($relationName): ?Model {
            if (! method_exists($this, $relationName)) {
                return null;
            }

            /** @phpstan-ignore-next-line */
            if ($this->relationLoaded($relationName)) {
                /** @phpstan-ignore-next-line */
                return $this->getRelation($relationName)->first();
            }

            /** @phpstan-ignore-next-line */
            return $this->{$relationName}()->first();
        });
    }

    /**
     * Boot resource configurations.
     */
    protected function bootResourceConfigs(): void
    {
        // Skip if no registries provided (for standalone package testing)
        if (empty($this->registries)) {
            return;
        }

        $registry = $this->registries['resourceConfig'] ?? null;

        if ($registry === null) {
            return;
        }

        foreach ($this->resourceConfigs as $resourceClass => $config) {
            // Eager loading
            foreach ($config['eagerLoad'] as $context => $relations) {
                if (method_exists($registry, 'addEagerLoad')) {
                    $registry->addEagerLoad($resourceClass, $context, $relations);
                }
            }

            // WithCount
            if (! empty($config['withCount']) && method_exists($registry, 'addWithCount')) {
                $registry->addWithCount($resourceClass, $config['withCount']);
            }

            // Table columns
            foreach ($config['tableColumns'] as $column) {
                if (method_exists($registry, 'addTableColumn')) {
                    $registry->addTableColumn(
                        $resourceClass,
                        $column['name'],
                        $column['callback'],
                        $column['priority']
                    );
                }
            }
        }
    }

    /**
     * Boot relation managers.
     */
    protected function bootRelationManagers(): void
    {
        if (empty($this->registries)) {
            return;
        }

        $registry = $this->registries['relationManager'] ?? null;

        if ($registry === null) {
            return;
        }

        foreach ($this->relationManagers as $rm) {
            if (method_exists($registry, 'register')) {
                $registry->register($rm['resource'], $rm['manager']);
            }
        }
    }

    /**
     * Boot form sections.
     */
    protected function bootFormSections(): void
    {
        if (empty($this->registries)) {
            return;
        }

        $registry = $this->registries['formComponent'] ?? null;

        if ($registry === null) {
            return;
        }

        foreach ($this->formSections as $section) {
            if (method_exists($registry, 'registerSection')) {
                $registry->registerSection($section['resource'], $section['callback']);
            }
        }
    }

    /**
     * Boot Livewire components.
     */
    protected function bootLivewireComponents(): void
    {
        foreach ($this->livewireComponents as $alias => $componentClass) {
            if (class_exists(Livewire::class)) {
                Livewire::component($alias, $componentClass);
            }
        }
    }

    /**
     * Boot custom callbacks.
     */
    protected function bootCustomCallbacks(): void
    {
        foreach ($this->bootCallbacks as $callback) {
            $callback($this);
        }
    }
}
