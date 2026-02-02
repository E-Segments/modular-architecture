<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit;

use Esegments\ModularArchitecture\Exceptions\InvalidManifestException;
use Esegments\ModularArchitecture\Module\ModuleManifest;
use Esegments\ModularArchitecture\Tests\TestCase;

class ModuleManifestTest extends TestCase
{
    public function test_can_create_manifest_from_array(): void
    {
        $data = [
            'name' => 'Blog',
            'version' => '1.0.0',
            'alias' => 'blog',
            'description' => 'A blog module',
            'priority' => 10,
            'providers' => ['Modules\\Blog\\Providers\\BlogServiceProvider'],
            'requires' => ['Core' => '^1.0'],
        ];

        $manifest = ModuleManifest::fromArray($data);

        $this->assertEquals('Blog', $manifest->name);
        $this->assertEquals('1.0.0', $manifest->version);
        $this->assertEquals('blog', $manifest->alias);
        $this->assertEquals('A blog module', $manifest->description);
        $this->assertEquals(10, $manifest->priority);
        $this->assertCount(1, $manifest->providers);
        $this->assertEquals('^1.0', $manifest->requires['Core']);
    }

    public function test_throws_exception_for_missing_name(): void
    {
        $this->expectException(InvalidManifestException::class);

        ModuleManifest::fromArray([
            'version' => '1.0.0',
        ]);
    }

    public function test_throws_exception_for_missing_version(): void
    {
        $this->expectException(InvalidManifestException::class);

        ModuleManifest::fromArray([
            'name' => 'Blog',
        ]);
    }

    public function test_defaults_alias_to_lowercase_name(): void
    {
        $manifest = ModuleManifest::fromArray([
            'name' => 'UserManagement',
            'version' => '1.0.0',
        ]);

        $this->assertEquals('usermanagement', $manifest->alias);
    }

    public function test_can_serialize_to_json(): void
    {
        $manifest = new ModuleManifest(
            name: 'Blog',
            version: '1.0.0',
            alias: 'blog',
            description: 'Test',
            priority: 5,
            providers: ['TestProvider'],
            requires: ['Core' => '^1.0'],
        );

        $json = $manifest->toJson();
        $decoded = json_decode($json, true);

        $this->assertEquals('Blog', $decoded['name']);
        $this->assertEquals('1.0.0', $decoded['version']);
    }

    public function test_can_check_requirements(): void
    {
        $manifest = new ModuleManifest(
            name: 'Blog',
            version: '1.0.0',
            requires: ['Core' => '^1.0', 'Users' => '>=2.0'],
        );

        $this->assertTrue($manifest->hasRequirement('Core'));
        $this->assertTrue($manifest->hasRequirement('Users'));
        $this->assertFalse($manifest->hasRequirement('NonExistent'));

        $this->assertEquals('^1.0', $manifest->getRequiredVersion('Core'));
        $this->assertNull($manifest->getRequiredVersion('NonExistent'));
    }
}
