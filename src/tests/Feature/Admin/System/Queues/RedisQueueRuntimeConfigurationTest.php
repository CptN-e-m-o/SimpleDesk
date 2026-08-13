<?php

namespace Tests\Feature\Admin\System\Queues;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\QueueConfigurationMode;
use App\Enums\Admin\System\QueueDriverType;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\QueueDriverSettings;
use App\Services\Admin\System\Queues\QueueRuntimeConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\RedisManager;
use ReflectionClass;
use Tests\TestCase;

class RedisQueueRuntimeConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_managed_infrastructure_connection_registers_dynamic_redis_runtime(): void
    {
        $infrastructure =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Managed,

                    'configuration' => [
                        'host' => 'runtime-redis.internal',

                        'port' => 6380,

                        'database' => 4,

                        'username' => 'queue-user',

                        'tls' => true,

                        'connect_timeout_seconds' => 3,
                    ],

                    'credentials' => [
                        'password' => 'runtime-secret',
                    ],

                    'is_enabled' => true,
                ]);

        $queue =
            $this->queueConfiguration(
                $infrastructure,
            );

        $this->activate(
            $queue,
        );

        $this
            ->app
            ->make(
                QueueRuntimeConfigurator::class,
            )
            ->apply();

        $name =
            'simpledesk-infrastructure-'
            .$infrastructure->id;

        $this->assertSame(
            'simpledesk-managed',
            config(
                'queue.default',
            ),
        );

        $this->assertSame(
            'redis',
            config(
                'queue.connections.simpledesk-managed.driver',
            ),
        );

        $this->assertSame(
            $name,
            config(
                'queue.connections.simpledesk-managed.connection',
            ),
        );

        $this->assertSame(
            'runtime-redis.internal',
            config(
                "database.redis.{$name}.host",
            ),
        );

        $this->assertSame(
            6380,
            config(
                "database.redis.{$name}.port",
            ),
        );

        $this->assertSame(
            4,
            config(
                "database.redis.{$name}.database",
            ),
        );

        $this->assertSame(
            'queue-user',
            config(
                "database.redis.{$name}.username",
            ),
        );

        $this->assertSame(
            'runtime-secret',
            config(
                "database.redis.{$name}.password",
            ),
        );

        $this->assertSame(
            'tls',
            config(
                "database.redis.{$name}.scheme",
            ),
        );

        $this->assertArrayNotHasKey(
            'password',
            $queue->configuration,
        );

        $this->assertArrayNotHasKey(
            'host',
            $queue->configuration,
        );

        $this->assertArrayNotHasKey(
            'port',
            $queue->configuration,
        );

        $this->assertArrayNotHasKey(
            'username',
            $queue->configuration,
        );

        $this->assertArrayNotHasKey(
            'tls',
            $queue->configuration,
        );
    }

    public function test_deployment_infrastructure_connection_reuses_laravel_redis_name(): void
    {
        config()->set(
            'database.redis.queue-deployment',
            [
                'host' => 'redis',

                'port' => 6379,

                'database' => 0,
            ],
        );

        $infrastructure =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Deployment,

                    'configuration' => [
                        'connection_name' => 'queue-deployment',
                    ],

                    'credentials' => [],

                    'is_enabled' => true,
                ]);

        $queue =
            $this->queueConfiguration(
                $infrastructure,
            );

        $this->activate(
            $queue,
        );

        $this
            ->app
            ->make(
                QueueRuntimeConfigurator::class,
            )
            ->apply();

        $this->assertSame(
            'queue-deployment',
            config(
                'queue.connections.simpledesk-managed.connection',
            ),
        );

        $this->assertNull(
            config(
                'database.redis.simpledesk-infrastructure-'
                .$infrastructure->id,
            ),
        );
    }

    public function test_managed_redis_runtime_refreshes_an_already_resolved_redis_manager(): void
    {
        $originalManager =
            $this->app->make(
                'redis',
            );

        $this->assertInstanceOf(
            RedisManager::class,
            $originalManager,
        );

        $infrastructure =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Managed,

                    'configuration' => [
                        'host' => 'late-runtime-redis.internal',

                        'port' => 6381,

                        'database' => 8,

                        'username' => '',

                        'tls' => false,

                        'connect_timeout_seconds' => 2,
                    ],

                    'credentials' => [
                        'password' => 'late-runtime-secret',
                    ],

                    'is_enabled' => true,
                ]);

        $queue =
            $this->queueConfiguration(
                $infrastructure,
            );

        $this->activate(
            $queue,
        );

        $this
            ->app
            ->make(
                QueueRuntimeConfigurator::class,
            )
            ->apply();

        $refreshedManager =
            $this->app->make(
                'redis',
            );

        $this->assertInstanceOf(
            RedisManager::class,
            $refreshedManager,
        );

        $this->assertNotSame(
            $originalManager,
            $refreshedManager,
        );

        $runtimeName =
            'simpledesk-infrastructure-'
            .$infrastructure->id;

        $managerConfiguration =
            $this->redisManagerConfiguration(
                $refreshedManager,
            );

        $this->assertArrayHasKey(
            $runtimeName,
            $managerConfiguration,
        );

        $runtimeConfiguration =
            $managerConfiguration[
            $runtimeName
            ];

        $this->assertSame(
            'late-runtime-redis.internal',
            $runtimeConfiguration[
            'host'
            ],
        );

        $this->assertSame(
            6381,
            $runtimeConfiguration[
            'port'
            ],
        );

        $this->assertSame(
            8,
            $runtimeConfiguration[
            'database'
            ],
        );

        $this->assertSame(
            'late-runtime-secret',
            $runtimeConfiguration[
            'password'
            ],
        );

        $oldManagerConfiguration =
            $this->redisManagerConfiguration(
                $originalManager,
            );

        $this->assertArrayNotHasKey(
            $runtimeName,
            $oldManagerConfiguration,
        );
    }

    private function queueConfiguration(
        InfrastructureConnection $infrastructure,
    ): QueueDriverConfiguration {
        return QueueDriverConfiguration::query()
            ->create([
                'name' => 'Managed Redis queue',

                'driver' => QueueDriverType::Redis,

                'configuration' => [
                    'infrastructure_connection_id' => $infrastructure->id,

                    'retry_after' => 360,

                    'block_for' => 5,

                    'after_commit' => false,
                ],

                'is_enabled' => true,
            ]);
    }

    private function activate(
        QueueDriverConfiguration $queue,
    ): void {
        QueueDriverSettings::query()
            ->create([
                'id' => QueueDriverSettings::SINGLETON_ID,

                'mode' => QueueConfigurationMode::Managed,

                'active_configuration_id' => $queue->id,

                'worker_restart_required' => false,
            ]);
    }

    /**
     * Read the RedisManager configuration without resolving
     * an actual Redis connection.
     *
     * @return array<string, mixed>
     */
    private function redisManagerConfiguration(
        RedisManager $manager,
    ): array {
        $reflection =
            new ReflectionClass(
                $manager,
            );

        $property =
            $reflection->getProperty(
                'config',
            );

        $configuration =
            $property->getValue(
                $manager,
            );

        $this->assertIsArray(
            $configuration,
        );

        return $configuration;
    }
}
