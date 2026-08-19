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
            'deployment-array',
        );

        config()->set(
            'cache.stores.deployment-array',
            [
                'driver' => 'array',
                'serialize' => false,
            ],
        );

        config()->set(
            'cache.default',
            'simpledesk-managed',
        );

        $target = $this->service()
            ->resolve();

        $this->assertSame(
            'deployment-array',
            $target['store'],
        );

        $this->assertSame(
            'array',
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
                'driver' => 'array',
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
            'driver' => 'array',
            'serialize' => false,
        ];

        config()->set(
            'simpledesk-cache.deployment.store',
            'deployment-array',
        );

        config()->set(
            'cache.stores.deployment-array',
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
                    'store' => 'deployment-array',
                    'driver' => 'array',
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
            'deployment-array',
        );

        config()->set(
            'cache.stores.deployment-array',
            [
                'driver' => 'array',
                'serialize' => false,
                'secret_value' => 'must-not-be-returned',
            ],
        );

        $target = $this->service()
            ->safeTarget();

        $this->assertSame(
            [
                'store' => 'deployment-array',
                'driver' => 'array',
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
