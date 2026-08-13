<?php

namespace Tests\Feature\Admin\System\InfrastructureConnections;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Exceptions\Admin\System\Infrastructure\InvalidRedisInfrastructureRuntimeConfigurationException;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureRuntimeConfigurationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedisInfrastructureRuntimeConfigurationFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_managed_redis_runtime_configuration_without_copying_unrelated_connection_values(): void
    {
        config()->set(
            'database.redis.default.max_retries',
            7,
        );

        config()->set(
            'database.redis.default.backoff_algorithm',
            'decorrelated_jitter',
        );

        config()->set(
            'database.redis.default.backoff_base',
            150,
        );

        config()->set(
            'database.redis.default.backoff_cap',
            1200,
        );

        config()->set(
            'database.redis.default.host',
            'deployment-redis',
        );

        config()->set(
            'database.redis.default.database',
            99,
        );

        config()->set(
            'database.redis.default.password',
            'deployment-password',
        );

        $connection =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Managed,

                    'configuration' => [
                        'host' => 'managed-redis.internal',

                        'port' => 6380,

                        'database' => 12,

                        'username' => 'simpledesk',

                        'tls' => true,

                        'connect_timeout_seconds' => 2.5,
                    ],

                    'credentials' => [
                        'password' => 'managed-password',
                    ],

                    'is_enabled' => true,
                ]);

        $runtime =
            $this
                ->factory()
                ->make(
                    $connection,
                );

        $this->assertSame(
            'managed-redis.internal',
            $runtime[
            'host'
            ],
        );

        $this->assertSame(
            6380,
            $runtime[
            'port'
            ],
        );

        $this->assertSame(
            12,
            $runtime[
            'database'
            ],
        );

        $this->assertSame(
            'simpledesk',
            $runtime[
            'username'
            ],
        );

        $this->assertSame(
            'managed-password',
            $runtime[
            'password'
            ],
        );

        $this->assertSame(
            2.5,
            $runtime[
            'timeout'
            ],
        );

        $this->assertSame(
            'tls',
            $runtime[
            'scheme'
            ],
        );

        $this->assertArrayNotHasKey(
            'read_timeout',
            $runtime,
        );

        $this->assertSame(
            7,
            $runtime[
            'max_retries'
            ],
        );

        $this->assertSame(
            'decorrelated_jitter',
            $runtime[
            'backoff_algorithm'
            ],
        );

        $this->assertSame(
            150,
            $runtime[
            'backoff_base'
            ],
        );

        $this->assertSame(
            1200,
            $runtime[
            'backoff_cap'
            ],
        );

        $this->assertNotSame(
            'deployment-redis',
            $runtime[
            'host'
            ],
        );

        $this->assertNotSame(
            99,
            $runtime[
            'database'
            ],
        );

        $this->assertNotSame(
            'deployment-password',
            $runtime[
            'password'
            ],
        );
    }

    public function test_empty_username_is_normalized_to_null(): void
    {
        $connection =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Managed,

                    'configuration' => [
                        'host' => 'redis.internal',

                        'port' => 6379,

                        'database' => 0,

                        'username' => '',

                        'tls' => false,

                        'connect_timeout_seconds' => 5,
                    ],

                    'is_enabled' => true,
                ]);

        $runtime =
            $this
                ->factory()
                ->make(
                    $connection,
                );

        $this->assertNull(
            $runtime[
            'username'
            ],
        );
    }

    public function test_disabled_connection_is_rejected(): void
    {
        $connection =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Managed,

                    'is_enabled' => false,
                ]);

        $this->expectException(
            InvalidRedisInfrastructureRuntimeConfigurationException::class,
        );

        $this->expectExceptionMessage(
            'disabled',
        );

        $this
            ->factory()
            ->make(
                $connection,
            );
    }

    public function test_archived_connection_is_rejected(): void
    {
        $connection =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Managed,

                    'is_enabled' => true,
                ]);

        $connection->delete();

        $this->expectException(
            InvalidRedisInfrastructureRuntimeConfigurationException::class,
        );

        $this->expectExceptionMessage(
            'archived',
        );

        $this
            ->factory()
            ->make(
                $connection,
            );
    }

    public function test_deployment_connection_is_rejected_by_managed_runtime_factory(): void
    {
        $connection =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Deployment,

                    'configuration' => [
                        'connection_name' => 'default',
                    ],

                    'credentials' => [],

                    'is_enabled' => true,
                ]);

        $this->expectException(
            InvalidRedisInfrastructureRuntimeConfigurationException::class,
        );

        $this->expectExceptionMessage(
            'not managed',
        );

        $this
            ->factory()
            ->make(
                $connection,
            );
    }

    public function test_corrupted_managed_configuration_is_rejected_with_domain_exception(): void
    {
        $connection =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Managed,

                    /*
                     * host is deliberately missing.
                     */
                    'configuration' => [
                        'port' => 6379,

                        'database' => 0,

                        'username' => '',

                        'tls' => false,

                        'connect_timeout_seconds' => 5,
                    ],

                    'is_enabled' => true,
                ]);

        $this->expectException(
            InvalidRedisInfrastructureRuntimeConfigurationException::class,
        );

        $this->expectExceptionMessage(
            'invalid Redis runtime configuration',
        );

        $this
            ->factory()
            ->make(
                $connection,
            );
    }

    private function factory(): RedisInfrastructureRuntimeConfigurationFactory
    {
        return $this->app->make(
            RedisInfrastructureRuntimeConfigurationFactory::class,
        );
    }
}
