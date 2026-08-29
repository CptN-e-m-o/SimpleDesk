<?php

namespace Tests\Unit\Admin\System\Cache;

use App\Enums\Admin\System\CacheHealthStatus;
use App\Services\Admin\System\Cache\CacheStoreHealthProbe;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureRuntimeConnectionRegistrar;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Lock;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store;
use Tests\TestCase;

class CacheStoreHealthProbeTest extends TestCase
{
    public function test_healthy_store_verifies_cache_and_lock_operations(): void
    {
        $store = new CacheProbeStore;

        $result = $this->probe($store)->test([
            'driver' => 'test',
        ]);

        $this->assertSame(
            CacheHealthStatus::Healthy,
            $result->status,
        );

        $this->assertTrue($store->lockReleaseAttempted);
        $this->assertTrue($store->lockReleased);
        $this->assertFalse($store->lockForceReleased);
        $this->assertSame([], $store->items);
    }

    public function test_failed_lock_release_is_reported_as_unhealthy(): void
    {
        $store = new CacheProbeStore(
            releaseSucceeds: false,
        );

        $result = $this->probe($store)->test([
            'driver' => 'test',
        ]);

        $this->assertSame(
            CacheHealthStatus::Unhealthy,
            $result->status,
        );

        $this->assertSame(
            'Cache atomic lock release failed.',
            $result->message,
        );

        $this->assertTrue($store->lockReleaseAttempted);
        $this->assertFalse($store->lockReleased);
        $this->assertTrue($store->lockForceReleased);
        $this->assertNull($store->lockOwner);
    }

    public function test_failed_write_is_reported_as_unhealthy(): void
    {
        $store = new CacheProbeStore(
            writeSucceeds: false,
        );

        $result = $this->probe($store)->test([
            'driver' => 'test',
        ]);

        $this->assertSame(
            CacheHealthStatus::Unhealthy,
            $result->status,
        );

        $this->assertSame(
            'Cache write verification failed.',
            $result->message,
        );

        $this->assertFalse($store->lockReleaseAttempted);
    }

    private function probe(
        CacheProbeStore $store,
    ): CacheStoreHealthProbe {
        $repository = new Repository($store);

        $cache = $this->createMock(
            CacheManager::class,
        );

        $cache
            ->method('store')
            ->willReturn($repository);

        $registrar = $this->createMock(
            RedisInfrastructureRuntimeConnectionRegistrar::class,
        );

        $registrar
            ->expects($this->once())
            ->method('registerMany');

        return new CacheStoreHealthProbe(
            cache: $cache,
            redisRegistrar: $registrar,
        );
    }
}

class CacheProbeStore implements LockProvider, Store
{
    public array $items = [];

    public ?string $lockOwner = null;

    public bool $lockReleaseAttempted = false;

    public bool $lockReleased = false;

    public bool $lockForceReleased = false;

    public function __construct(
        public bool $releaseSucceeds = true,
        public bool $writeSucceeds = true,
    ) {}

    public function get($key): mixed
    {
        return $this->items[$key] ?? null;
    }

    public function many(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    public function put(
        $key,
        $value,
        $seconds,
    ): bool {
        if (! $this->writeSucceeds) {
            return false;
        }

        $this->items[$key] = $value;

        return true;
    }

    public function putMany(
        array $values,
        $seconds,
    ): bool {
        foreach ($values as $key => $value) {
            if (! $this->put($key, $value, $seconds)) {
                return false;
            }
        }

        return true;
    }

    public function increment(
        $key,
        $value = 1,
    ): int|bool {
        $current = (int) ($this->items[$key] ?? 0);
        $current += $value;
        $this->items[$key] = $current;

        return $current;
    }

    public function decrement(
        $key,
        $value = 1,
    ): int|bool {
        return $this->increment(
            $key,
            -$value,
        );
    }

    public function forever(
        $key,
        $value,
    ): bool {
        return $this->put(
            $key,
            $value,
            0,
        );
    }

    public function touch(
        $key,
        $seconds,
    ): bool {
        return array_key_exists(
            $key,
            $this->items,
        );
    }

    public function forget($key): bool
    {
        $exists = array_key_exists(
            $key,
            $this->items,
        );

        unset($this->items[$key]);

        return $exists;
    }

    public function flush(): bool
    {
        $this->items = [];

        return true;
    }

    public function getPrefix(): string
    {
        return '';
    }

    public function lock(
        $name,
        $seconds = 0,
        $owner = null,
    ): CacheProbeLock {
        return new CacheProbeLock(
            $this,
            $name,
            $seconds,
            $owner,
        );
    }

    public function restoreLock(
        $name,
        $owner,
    ): CacheProbeLock {
        return $this->lock(
            $name,
            0,
            $owner,
        );
    }
}

class CacheProbeLock extends Lock
{
    public function __construct(
        private readonly CacheProbeStore $store,
        string $name,
        int $seconds,
        ?string $owner = null,
    ) {
        parent::__construct(
            $name,
            $seconds,
            $owner,
        );
    }

    public function acquire(): bool
    {
        if ($this->store->lockOwner !== null) {
            return false;
        }

        $this->store->lockOwner = $this->owner();

        return true;
    }

    public function release(): bool
    {
        $this->store->lockReleaseAttempted = true;

        if (! $this->store->releaseSucceeds) {
            return false;
        }

        if ($this->store->lockOwner !== $this->owner()) {
            return false;
        }

        $this->store->lockOwner = null;
        $this->store->lockReleased = true;

        return true;
    }

    public function forceRelease(): void
    {
        $this->store->lockOwner = null;
        $this->store->lockForceReleased = true;
    }

    protected function getCurrentOwner(): ?string
    {
        return $this->store->lockOwner;
    }
}
