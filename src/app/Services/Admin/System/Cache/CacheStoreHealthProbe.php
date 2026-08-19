<?php

namespace App\Services\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureRuntimeConnectionRegistrar;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Str;
use Throwable;

class CacheStoreHealthProbe
{
    public function __construct(private readonly CacheManager $cache, private readonly RedisInfrastructureRuntimeConnectionRegistrar $redisRegistrar) {}
    public function test(array $store, array $redisConnections = [], array $details = []): CacheHealthResultData
    {
        $started = hrtime(true); $name = 'simpledesk-health-'.Str::lower(Str::random(12)); $key = 'simpledesk:health:'.Str::uuid(); $lockKey = $key.':lock'; $value = Str::random(48);
        try {
            $this->redisRegistrar->registerMany($redisConnections); config()->set("cache.stores.{$name}", $store); $this->cache->forgetDriver($name); $repository = $this->cache->store($name);
            if (! $repository->getStore() instanceof LockProvider) return $this->result(CacheHealthStatus::Unhealthy, $started, 'Cache storage does not provide the required atomic lock support.', $details);
            $repository->put($key, $value, (int) config('simpledesk-cache.health.ttl_seconds', 30));
            if ($repository->get($key) !== $value) return $this->result(CacheHealthStatus::Unhealthy, $started, 'Cache write/read verification failed.', $details);
            if (! $repository->forget($key) || $repository->has($key)) return $this->result(CacheHealthStatus::Unhealthy, $started, 'Cache delete verification failed.', $details);
            $lock = $repository->lock($lockKey, (int) config('simpledesk-cache.health.lock_seconds', 10));
            if (! $lock->get()) return $this->result(CacheHealthStatus::Unhealthy, $started, 'Cache atomic lock acquisition failed.', $details);
            try { $lock->release(); } catch (Throwable) { return $this->result(CacheHealthStatus::Unhealthy, $started, 'Cache atomic lock release failed.', $details); }
            return $this->result(CacheHealthStatus::Healthy, $started, 'Cache write, read, delete, and atomic lock operations succeeded.', $details);
        } catch (Throwable) { return $this->result(CacheHealthStatus::Unavailable, $started, 'Cache target is unavailable.', $details); }
        finally { try { $this->cache->store($name)->forget($key); } catch (Throwable) {} $this->cache->forgetDriver($name); config()->offsetUnset("cache.stores.{$name}"); }
    }
    private function result(CacheHealthStatus $status, int $started, string $message, array $details): CacheHealthResultData { return new CacheHealthResultData($status, (int) round((hrtime(true) - $started) / 1_000_000), $message, $details); }
}
