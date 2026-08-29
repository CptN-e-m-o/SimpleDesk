<?php

namespace Tests\Feature\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Services\Admin\System\Cache\CacheDeploymentTargetService;
use App\Services\Admin\System\Cache\CacheStoreHealthProbe;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CacheDeploymentTargetServiceTest extends TestCase
{
    public function test_deployment_target_remains_stable_when_runtime_default_changes(): void
    {
        config()->set(
            'simpledesk-cache.deployment.store',
            'database',
        );

        config()->set(
            'cache.default',
            'simpledesk-managed',
        );

        $target = $this->service()
            ->resolve();

        $this->assertSame(
            'database',
            $target['store'],
        );

        $this->assertSame(
            'database',
            $target['driver'],
        );
    }

    public function test_default_database_store_accepts_null_lock_table_and_uses_framework_fallback(): void
    {
        config()->set(
            'simpledesk-cache.deployment.store',
            'database',
        );

        config()->set(
            'cache.stores.database.lock_table',
            null,
        );

        $target = $this->service()
            ->resolve();

        $this->assertSame(
            'database',
            $target['driver'],
        );
    }

    public function test_file_store_accepts_null_lock_path(): void
    {
        config()->set(
            'simpledesk-cache.deployment.store',
            'deployment-file',
        );

        config()->set(
            'cache.stores.deployment-file',
            [
                'driver' => 'file',
                'path' => storage_path(
                    'framework/cache/deployment-test',
                ),
                'lock_path' => null,
            ],
        );

        $target = $this->service()
            ->resolve();

        $this->assertSame(
            'file',
            $target['driver'],
        );
    }

    public function test_missing_deployment_store_is_rejected(): void
    {
        config()->set(
            'simpledesk-cache.deployment.store',
            'missing-store',
        );

        config()->set(
            'cache.stores.missing-store',
            null,
        );

        $this->expectException(
            ValidationException::class,
        );

        $this->service()
            ->resolve();
    }

    public function test_managed_runtime_store_cannot_be_deployment_target(): void
    {
        config()->set(
            'simpledesk-cache.runtime.store_name',
            'simpledesk-managed',
        );

        config()->set(
            'simpledesk-cache.deployment.store',
            'simpledesk-managed',
        );

        config()->set(
            'cache.stores.simpledesk-managed',
            [
                'driver' => 'database',
                'table' => 'cache',
            ],
        );

        $this->expectException(
            ValidationException::class,
        );

        $this->service()
            ->resolve();
    }

    public function test_unknown_deployment_driver_is_rejected_structurally(): void
    {
        config()->set(
            'simpledesk-cache.deployment.store',
            'broken-store',
        );

        config()->set(
            'cache.stores.broken-store',
            [
                'driver' => 'not-a-real-driver',
            ],
        );

        $this->expectException(
            ValidationException::class,
        );

        $this->service()
            ->resolve();
    }

    public function test_array_driver_is_rejected_structurally(): void
    {
        config()->set(
            'simpledesk-cache.deployment.store',
            'deployment-array',
        );

        config()->set(
            'cache.stores.deployment-array',
            [
                'driver' => 'array',
                'serialize' => false,
            ],
        );

        $this->expectException(
            ValidationException::class,
        );

        $this->service()
            ->resolve();
    }

    public function test_null_driver_is_rejected_structurally(): void
    {
        config()->set(
            'simpledesk-cache.deployment.store',
            'deployment-null',
        );

        config()->set(
            'cache.stores.deployment-null',
            [
                'driver' => 'null',
            ],
        );

        $this->expectException(
            ValidationException::class,
        );

        $this->service()
            ->resolve();
    }

    public function test_failover_driver_is_rejected_until_supported(): void
    {
        config()->set(
            'simpledesk-cache.deployment.store',
            'deployment-failover',
        );

        config()->set(
            'cache.stores.deployment-failover',
            [
                'driver' => 'failover',
                'stores' => [
                    'database',
                    'file',
                ],
            ],
        );

        $this->expectException(
            ValidationException::class,
        );

        $this->service()
            ->resolve();
    }

    public function test_database_store_without_table_is_rejected_structurally(): void
    {
        $default = config(
            'database.default',
        );

        config()->set(
            'simpledesk-cache.deployment.store',
            'broken-database-cache',
        );

        config()->set(
            'cache.stores.broken-database-cache',
            [
                'driver' => 'database',
                'connection' => $default,
                'lock_connection' => $default,
            ],
        );

        $this->expectException(
            ValidationException::class,
        );

        $this->service()
            ->resolve();
    }

    public function test_database_store_with_empty_lock_table_is_rejected_structurally(): void
    {
        $default = config(
            'database.default',
        );

        config()->set(
            'simpledesk-cache.deployment.store',
            'broken-database-lock-cache',
        );

        config()->set(
            'cache.stores.broken-database-lock-cache',
            [
                'driver' => 'database',
                'connection' => $default,
                'table' => 'cache',
                'lock_connection' => $default,
                'lock_table' => '',
            ],
        );

        $this->expectException(
            ValidationException::class,
        );

        $this->service()
            ->resolve();
    }

    public function test_redis_store_with_missing_connection_is_rejected_structurally(): void
    {
        config()->set(
            'simpledesk-cache.deployment.store',
            'broken-redis-cache',
        );

        config()->set(
            'cache.stores.broken-redis-cache',
            [
                'driver' => 'redis',
                'connection' => 'missing-redis',
                'lock_connection' => 'missing-redis',
            ],
        );

        config()->set(
            'database.redis.missing-redis',
            null,
        );

        $this->expectException(
            ValidationException::class,
        );

        $this->service()
            ->resolve();
    }

    public function test_file_store_without_path_is_rejected_structurally(): void
    {
        config()->set(
            'simpledesk-cache.deployment.store',
            'broken-file-cache',
        );

        config()->set(
            'cache.stores.broken-file-cache',
            [
                'driver' => 'file',
            ],
        );

        $this->expectException(
            ValidationException::class,
        );

        $this->service()
            ->resolve();
    }

    public function test_valid_target_is_passed_to_health_probe(): void
    {
        $store = [
            'driver' => 'file',
            'path' => storage_path(
                'framework/cache/deployment-probe',
            ),
            'lock_path' => storage_path(
                'framework/cache/deployment-probe-locks',
            ),
        ];

        config()->set(
            'simpledesk-cache.deployment.store',
            'deployment-file',
        );

        config()->set(
            'cache.stores.deployment-file',
            $store,
        );

        $probe = $this->createMock(
            CacheStoreHealthProbe::class,
        );

        $probe
            ->expects($this->once())
            ->method('test')
            ->with(
                $store,
                [],
                [
                    'store' => 'deployment-file',
                    'driver' => 'file',
                ],
            )
            ->willReturn(
                new CacheHealthResultData(
                    status: CacheHealthStatus::Healthy,
                    latencyMs: 2,
                    message: 'Deployment Cache verified.',
                ),
            );

        $result = (
        new CacheDeploymentTargetService(
            $probe,
        )
        )->test();

        $this->assertSame(
            CacheHealthStatus::Healthy,
            $result->status,
        );
    }

    public function test_safe_target_does_not_expose_store_configuration(): void
    {
        config()->set(
            'simpledesk-cache.deployment.store',
            'deployment-file',
        );

        config()->set(
            'cache.stores.deployment-file',
            [
                'driver' => 'file',
                'path' => storage_path(
                    'framework/cache/deployment-safe-target',
                ),
                'secret_value' => 'must-not-be-returned',
            ],
        );

        $target = $this->service()
            ->safeTarget();

        $this->assertSame(
            [
                'store' => 'deployment-file',
                'driver' => 'file',
                'available' => true,
            ],
            $target,
        );
    }

    private function service(): CacheDeploymentTargetService
    {
        return new CacheDeploymentTargetService(
            $this->createMock(
                CacheStoreHealthProbe::class,
            ),
        );
    }
}
