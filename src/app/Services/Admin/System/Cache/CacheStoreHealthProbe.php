<?php

namespace App\Services\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureRuntimeConnectionRegistrar;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Str;
use Throwable;

class CacheStoreHealthProbe
{
    public function __construct(
        private readonly CacheManager $cache,
        private readonly RedisInfrastructureRuntimeConnectionRegistrar $redisRegistrar,
    ) {}

    public function test(
        array $store,
        array $redisConnections = [],
        array $details = [],
    ): CacheHealthResultData {
        $started = hrtime(true);
        $storeName = 'simpledesk-health-'.Str::lower(Str::random(12));
        $key = 'simpledesk:health:'.Str::uuid();
        $lockKey = $key.':lock';
        $value = Str::random(48);

        $lock = null;
        $lockAcquired = false;
        $lockReleased = false;

        try {
            $this->redisRegistrar->registerMany($redisConnections);

            config()->set("cache.stores.{$storeName}", $store);
            $this->cache->forgetDriver($storeName);

            $repository = $this->cache->store($storeName);

            if (! $repository->getStore() instanceof LockProvider) {
                return $this->result(
                    CacheHealthStatus::Unhealthy,
                    $started,
                    'Cache storage does not provide the required atomic lock support.',
                    $details,
                );
            }

            $written = $repository->put(
                $key,
                $value,
                (int) config('simpledesk-cache.health.ttl_seconds', 30),
            );

            if (! $written) {
                return $this->result(
                    CacheHealthStatus::Unhealthy,
                    $started,
                    'Cache write verification failed.',
                    $details,
                );
            }

            if ($repository->get($key) !== $value) {
                return $this->result(
                    CacheHealthStatus::Unhealthy,
                    $started,
                    'Cache write/read verification failed.',
                    $details,
                );
            }

            if (! $repository->forget($key) || $repository->has($key)) {
                return $this->result(
                    CacheHealthStatus::Unhealthy,
                    $started,
                    'Cache delete verification failed.',
                    $details,
                );
            }

            $lock = $repository->lock(
                $lockKey,
                (int) config('simpledesk-cache.health.lock_seconds', 10),
            );

            if (! $lock->get()) {
                return $this->result(
                    CacheHealthStatus::Unhealthy,
                    $started,
                    'Cache atomic lock acquisition failed.',
                    $details,
                );
            }

            $lockAcquired = true;

            try {
                if (! $lock->release()) {
                    return $this->result(
                        CacheHealthStatus::Unhealthy,
                        $started,
                        'Cache atomic lock release failed.',
                        $details,
                    );
                }

                $lockReleased = true;
            } catch (Throwable) {
                return $this->result(
                    CacheHealthStatus::Unhealthy,
                    $started,
                    'Cache atomic lock release failed.',
                    $details,
                );
            }

            return $this->result(
                CacheHealthStatus::Healthy,
                $started,
                'Cache write, read, delete, and atomic lock operations succeeded.',
                $details,
            );
        } catch (Throwable) {
            return $this->result(
                CacheHealthStatus::Unavailable,
                $started,
                'Cache target is unavailable.',
                $details,
            );
        } finally {
            $this->cleanupLock(
                $lock,
                $lockAcquired,
                $lockReleased,
            );

            try {
                $this->cache
                    ->store($storeName)
                    ->forget($key);
            } catch (Throwable) {
            }

            $this->cache->forgetDriver($storeName);
            config()->offsetUnset("cache.stores.{$storeName}");
        }
    }

    private function cleanupLock(
        ?Lock $lock,
        bool $acquired,
        bool $released,
    ): void {
        if (! $lock || ! $acquired || $released) {
            return;
        }

        try {
            $lock->forceRelease();
        } catch (Throwable) {
        }
    }

    private function result(
        CacheHealthStatus $status,
        int $started,
        string $message,
        array $details,
    ): CacheHealthResultData {
        return new CacheHealthResultData(
            status: $status,
            latencyMs: (int) round(
                (hrtime(true) - $started) / 1_000_000,
            ),
            message: $message,
            details: $details,
        );
    }
}
