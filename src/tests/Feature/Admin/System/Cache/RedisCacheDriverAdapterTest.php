<?php

namespace Tests\Feature\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Cache\CacheStoreHealthProbe;
use App\Services\Admin\System\Cache\Drivers\RedisCacheDriverAdapter;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureRuntimeConfigurationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RedisCacheDriverAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_managed_infrastructure_builds_synthetic_redis_connection(): void
    {
        $connection = InfrastructureConnection::factory()->create([
            'source' => InfrastructureConnectionSource::Managed,
        ]);

        $configuration = $this->configuration(
            $connection,
        );

        $runtimeFactory = $this->createMock(
            RedisInfrastructureRuntimeConfigurationFactory::class,
        );

        $runtimeFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->callback(
                fn (InfrastructureConnection $value): bool => $value->id === $connection->id,
            ))
            ->willReturn([
                'host' => 'redis.internal',
                'port' => 6379,
                'database' => 2,
                'password' => 'runtime-secret',
                'scheme' => 'tcp',
            ]);

        $adapter = new RedisCacheDriverAdapter(
            runtimeFactory: $runtimeFactory,
            probe: $this->createMock(
                CacheStoreHealthProbe::class,
            ),
        );

        $runtime = $adapter->runtimeConfiguration(
            $configuration,
        );

        $name = 'simpledesk-infrastructure-'.$connection->id;

        $this->assertSame(
            [
                'driver' => 'redis',
                'connection' => $name,
                'lock_connection' => $name,
            ],
            $runtime->store,
        );

        $this->assertArrayHasKey(
            $name,
            $runtime->redisConnections,
        );

        $this->assertSame(
            'runtime-secret',
            $runtime->redisConnections[$name]['password'],
        );
    }

    public function test_deployment_infrastructure_reuses_existing_laravel_redis_connection(): void
    {
        config()->set(
            'database.redis.cache-deployment',
            [
                'host' => 'redis',
                'port' => 6379,
                'database' => 3,
            ],
        );

        $connection = InfrastructureConnection::factory()->create([
            'source' => InfrastructureConnectionSource::Deployment,
            'configuration' => [
                'connection_name' => 'cache-deployment',
            ],
        ]);

        $configuration = $this->configuration(
            $connection,
        );

        $runtimeFactory = $this->createMock(
            RedisInfrastructureRuntimeConfigurationFactory::class,
        );

        $runtimeFactory
            ->expects($this->never())
            ->method('make');

        $adapter = new RedisCacheDriverAdapter(
            runtimeFactory: $runtimeFactory,
            probe: $this->createMock(
                CacheStoreHealthProbe::class,
            ),
        );

        $runtime = $adapter->runtimeConfiguration(
            $configuration,
        );

        $this->assertSame(
            [
                'driver' => 'redis',
                'connection' => 'cache-deployment',
                'lock_connection' => 'cache-deployment',
            ],
            $runtime->store,
        );

        $this->assertSame(
            [],
            $runtime->redisConnections,
        );
    }

    public function test_missing_infrastructure_connection_is_rejected(): void
    {
        $configuration = CacheDriverConfiguration::query()->create([
            'name' => 'Broken Redis Cache',
            'driver' => 'redis',
            'infrastructure_connection_id' => null,
            'configuration' => [],
            'is_enabled' => true,
        ]);

        $this->expectException(
            ValidationException::class,
        );

        $this->adapter()->runtimeConfiguration(
            $configuration,
        );
    }

    public function test_archived_infrastructure_connection_is_rejected(): void
    {
        $connection = InfrastructureConnection::factory()->create();
        $connection->delete();

        $this->expectException(
            ValidationException::class,
        );

        $this->adapter()->runtimeConfiguration(
            $this->configuration($connection),
        );
    }

    public function test_disabled_infrastructure_connection_is_rejected(): void
    {
        $connection = InfrastructureConnection::factory()->create([
            'is_enabled' => false,
        ]);

        $this->expectException(
            ValidationException::class,
        );

        $this->adapter()->runtimeConfiguration(
            $this->configuration($connection),
        );
    }

    public function test_wrong_infrastructure_type_is_rejected(): void
    {
        $connection = InfrastructureConnection::factory()->create([
            'type' => InfrastructureConnectionType::Aws,
        ]);

        $this->expectException(
            ValidationException::class,
        );

        $this->adapter()->runtimeConfiguration(
            $this->configuration($connection),
        );
    }

    public function test_nested_infrastructure_connection_id_is_rejected(): void
    {
        $connection = InfrastructureConnection::factory()->create();

        $configuration = CacheDriverConfiguration::query()->create([
            'name' => 'Nested Redis Cache',
            'driver' => 'redis',
            'infrastructure_connection_id' => $connection->id,
            'configuration' => [
                'infrastructure_connection_id' => $connection->id,
            ],
            'is_enabled' => true,
        ]);

        $this->expectException(
            ValidationException::class,
        );

        $this->adapter()->runtimeConfiguration(
            $configuration,
        );
    }

    public function test_health_probe_receives_safe_infrastructure_metadata(): void
    {
        $connection = InfrastructureConnection::factory()->create([
            'source' => InfrastructureConnectionSource::Managed,
        ]);

        $configuration = $this->configuration(
            $connection,
        );

        $runtimeFactory = $this->createMock(
            RedisInfrastructureRuntimeConfigurationFactory::class,
        );

        $runtimeFactory
            ->method('make')
            ->willReturn([
                'host' => 'redis',
                'port' => 6379,
                'database' => 0,
                'password' => 'secret',
                'scheme' => 'tcp',
            ]);

        $probe = $this->createMock(
            CacheStoreHealthProbe::class,
        );

        $probe
            ->expects($this->once())
            ->method('test')
            ->with(
                $this->callback(
                    fn (array $store): bool => $store['driver'] === 'redis',
                ),
                $this->callback(
                    fn (array $connections): bool => count($connections) === 1,
                ),
                [
                    'infrastructure_connection_id' => $connection->id,
                    'source' => 'managed',
                ],
            )
            ->willReturn(
                new CacheHealthResultData(
                    status: CacheHealthStatus::Healthy,
                    latencyMs: 4,
                    message: 'Cache target verified.',
                ),
            );

        $adapter = new RedisCacheDriverAdapter(
            runtimeFactory: $runtimeFactory,
            probe: $probe,
        );

        $result = $adapter->test(
            $configuration,
        );

        $this->assertSame(
            CacheHealthStatus::Healthy,
            $result->status,
        );
    }

    private function adapter(): RedisCacheDriverAdapter
    {
        return new RedisCacheDriverAdapter(
            runtimeFactory: app(
                RedisInfrastructureRuntimeConfigurationFactory::class,
            ),
            probe: $this->createMock(
                CacheStoreHealthProbe::class,
            ),
        );
    }

    private function configuration(
        InfrastructureConnection $connection,
    ): CacheDriverConfiguration {
        return CacheDriverConfiguration::query()->create([
            'name' => 'Redis Cache',
            'driver' => 'redis',
            'infrastructure_connection_id' => $connection->id,
            'configuration' => [],
            'is_enabled' => true,
        ]);
    }
}
