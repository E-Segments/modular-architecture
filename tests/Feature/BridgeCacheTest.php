<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Bridges\Cache\BridgeCache;
use Esegments\ModularArchitecture\Tests\TestCase;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Filesystem\Filesystem;

class BridgeCacheTest extends TestCase
{
    protected string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cachePath = sys_get_temp_dir().'/modular-bridge-cache-'.uniqid();
        @mkdir($this->cachePath, 0777, true);
    }

    public function test_cache_is_disabled_by_default_in_tests(): void
    {
        $cache = $this->createCache(enabled: false);

        $this->assertFalse($cache->isEnabled());
    }

    public function test_cache_can_be_enabled(): void
    {
        $cache = $this->createCache(enabled: true);

        $this->assertTrue($cache->isEnabled());
    }

    public function test_put_and_get_with_file_driver(): void
    {
        $cache = $this->createCache(enabled: true, driver: 'file');

        $data = [
            'livewire' => ['registered' => ['Module1' => []]],
            'blade' => ['registered' => ['Module2' => []]],
        ];

        $cache->put($data);
        $retrieved = $cache->get();

        $this->assertEquals($data, $retrieved);
    }

    public function test_has_returns_false_when_no_cache(): void
    {
        $cache = $this->createCache(enabled: true, driver: 'file');

        $this->assertFalse($cache->has());
    }

    public function test_has_returns_true_after_put(): void
    {
        $cache = $this->createCache(enabled: true, driver: 'file');

        $cache->put(['test' => 'data']);

        $this->assertTrue($cache->has());
    }

    public function test_clear_removes_cache(): void
    {
        $cache = $this->createCache(enabled: true, driver: 'file');

        $cache->put(['test' => 'data']);
        $this->assertTrue($cache->has());

        $cache->clear();
        $this->assertFalse($cache->has());
    }

    public function test_get_bridge_returns_specific_bridge_data(): void
    {
        $cache = $this->createCache(enabled: true, driver: 'file');

        $cache->put([
            'livewire' => ['components' => ['Button', 'Modal']],
            'blade' => ['views' => ['index', 'show']],
        ]);

        $livewireData = $cache->getBridge('livewire');

        $this->assertEquals(['components' => ['Button', 'Modal']], $livewireData);
    }

    public function test_get_bridge_returns_null_for_unknown_bridge(): void
    {
        $cache = $this->createCache(enabled: true, driver: 'file');

        $cache->put(['livewire' => []]);

        $this->assertNull($cache->getBridge('unknown'));
    }

    public function test_put_bridge_updates_specific_bridge(): void
    {
        $cache = $this->createCache(enabled: true, driver: 'file');

        $cache->put(['livewire' => ['old' => 'data']]);
        $cache->putBridge('blade', ['new' => 'data']);

        $data = $cache->get();

        $this->assertArrayHasKey('livewire', $data);
        $this->assertArrayHasKey('blade', $data);
        $this->assertEquals(['new' => 'data'], $data['blade']);
    }

    public function test_stats_returns_cache_information(): void
    {
        $cache = $this->createCache(enabled: true, driver: 'file');

        $stats = $cache->stats();

        $this->assertTrue($stats['enabled']);
        $this->assertEquals('file', $stats['driver']);
        $this->assertFalse($stats['has_cache']);
        $this->assertNotNull($stats['path']);
    }

    public function test_warm_stores_data(): void
    {
        $cache = $this->createCache(enabled: true, driver: 'file');

        $cache->warm(['warmed' => 'data']);

        $this->assertTrue($cache->has());
        $this->assertEquals(['warmed' => 'data'], $cache->get());
    }

    public function test_disabled_cache_returns_null_on_get(): void
    {
        $cache = $this->createCache(enabled: false);

        $cache->put(['test' => 'data']);

        $this->assertNull($cache->get());
    }

    protected function createCache(bool $enabled = true, string $driver = 'file'): BridgeCache
    {
        return new BridgeCache(
            cache: $this->app->make(CacheRepository::class),
            files: $this->app->make(Filesystem::class),
            cachePath: $this->cachePath,
            ttl: 3600,
            enabled: $enabled,
            driver: $driver,
        );
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->cachePath);
        parent::tearDown();
    }

    protected function recursiveDelete(string $dir): void
    {
        if (is_dir($dir)) {
            foreach (scandir($dir) as $item) {
                if ($item !== '.' && $item !== '..') {
                    $path = $dir.'/'.$item;
                    is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
                }
            }
            rmdir($dir);
        }
    }
}
