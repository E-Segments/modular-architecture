<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit\Links;

use Esegments\ModularArchitecture\Links\LinkDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the LinkDefinition fluent builder.
 */
class LinkDefinitionTest extends TestCase
{
    public function test_it_creates_link_definition_with_name(): void
    {
        $definition = new LinkDefinition('ProductBrandLink');

        $this->assertSame('ProductBrandLink', $definition->getName());
        $this->assertFalse($definition->isBooted());
    }

    public function test_it_sets_required_modules(): void
    {
        $definition = new LinkDefinition('ProductBrandLink');
        $definition->requires('Products', 'Brands');

        $this->assertSame(['Products', 'Brands'], $definition->getRequiredModules());
    }

    public function test_it_chains_method_calls_fluently(): void
    {
        $definition = new LinkDefinition('ProductBrandLink');

        $result = $definition
            ->requires('Products', 'Brands')
            ->belongsTo('Product', 'brand', 'Brand', 'brand_id')
            ->hasMany('Brand', 'products', 'Product', 'brand_id');

        $this->assertSame($definition, $result);
    }

    public function test_it_supports_all_relationship_types(): void
    {
        $definition = new LinkDefinition('TestLink');

        // These should all return self for chaining
        $definition
            ->belongsTo('Model', 'relation', 'Related')
            ->hasMany('Model', 'relation', 'Related')
            ->hasOne('Model', 'relation', 'Related')
            ->belongsToMany('Model', 'relation', 'Related', 'pivot_table')
            ->morphToMany('Model', 'relation', 'Related', 'taggable')
            ->morphedByMany('Model', 'relation', 'Related', 'taggable');

        $this->assertInstanceOf(LinkDefinition::class, $definition);
    }

    public function test_it_supports_all_macro_types(): void
    {
        $definition = new LinkDefinition('TestLink');

        // These should all return self for chaining
        $definition
            ->relatedNameAccessor('Model', 'brandName', 'brand')
            ->relatedNamesAccessor('Model', 'categoryNames', 'categories')
            ->hasRelationMacro('Model', 'hasBrand', 'brand_id')
            ->filterScope('Model', 'scopeOfBrand', 'brand_id')
            ->filterScopeViaRelation('Model', 'scopeInCategory', 'categories', 'categories')
            ->slugFilterScope('Model', 'scopeOfBrandSlug', 'brand')
            ->productCountMacro('Model')
            ->activeProductCountMacro('Model')
            ->withRelationScope('Model', 'scopeWithProducts', 'products')
            ->primaryRelationMacro('Model', 'primaryCategory', 'categories')
            ->customMacro('Model', 'custom', fn () => true);

        $this->assertInstanceOf(LinkDefinition::class, $definition);
    }

    public function test_it_supports_filament_integrations(): void
    {
        $definition = new LinkDefinition('TestLink');

        $definition
            ->eagerLoad('ResourceClass', 'table', ['products'])
            ->eagerLoad('ResourceClass', 'all', ['brand'])
            ->withCount('ResourceClass', ['products'])
            ->tableColumn('ResourceClass', 'column', fn () => null, 50)
            ->relationManager('ResourceClass', 'ManagerClass')
            ->formSection('ResourceClass', fn () => []);

        $this->assertInstanceOf(LinkDefinition::class, $definition);
    }

    public function test_it_supports_livewire_components(): void
    {
        $definition = new LinkDefinition('TestLink');

        $definition->livewireComponent('alias', 'ComponentClass');

        $this->assertInstanceOf(LinkDefinition::class, $definition);
    }

    public function test_it_supports_custom_boot_callbacks(): void
    {
        $definition = new LinkDefinition('TestLink');
        $callbackExecuted = false;

        $definition->onBoot(function () use (&$callbackExecuted): void {
            $callbackExecuted = true;
        });

        // Boot the definition (without any actual model classes)
        // The callback should execute even if there are no relationships
        $definition->boot();

        $this->assertTrue($callbackExecuted);
        $this->assertTrue($definition->isBooted());
    }

    public function test_boot_only_runs_once(): void
    {
        $definition = new LinkDefinition('TestLink');
        $callCount = 0;

        $definition->onBoot(function () use (&$callCount): void {
            $callCount++;
        });

        $definition->boot();
        $definition->boot();
        $definition->boot();

        $this->assertSame(1, $callCount);
    }

    public function test_it_can_set_registries(): void
    {
        $definition = new LinkDefinition('TestLink');
        $registries = ['resourceConfig' => new \stdClass];

        $result = $definition->setRegistries($registries);

        $this->assertSame($definition, $result);
    }

    public function test_belongs_to_many_with_options(): void
    {
        $definition = new LinkDefinition('TestLink');

        $result = $definition->belongsToMany(
            'Model',
            'tags',
            'Tag',
            'model_tag',
            'model_id',
            'tag_id',
            ['order', 'metadata'],
            true
        );

        $this->assertSame($definition, $result);
    }

    public function test_morph_to_many_with_options(): void
    {
        $definition = new LinkDefinition('TestLink');

        $result = $definition->morphToMany(
            'Model',
            'tags',
            'Tag',
            'taggable',
            'taggables',
            ['order'],
            true
        );

        $this->assertSame($definition, $result);
    }

    public function test_eager_load_all_context(): void
    {
        $definition = new LinkDefinition('TestLink');

        // Test 'all' context which should add to table, form, and view
        $definition->eagerLoad('ResourceClass', 'all', ['brand', 'category']);

        $this->assertInstanceOf(LinkDefinition::class, $definition);
    }
}
