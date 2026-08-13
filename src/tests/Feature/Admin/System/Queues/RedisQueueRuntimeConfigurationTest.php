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
use Tests\TestCase;

class RedisQueueRuntimeConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_managed_infrastructure_connection_registers_dynamic_redis_runtime(): void
    {
        $infrastructure = InfrastructureConnection::factory()->create([
            'source' => InfrastructureConnectionSource::Managed,
            'credentials' => ['password' => 'runtime-secret'],
        ]);
        $queue = $this->queueConfiguration($infrastructure);
        $this->activate($queue);
        $this->app->make(QueueRuntimeConfigurator::class)->apply();

        $name = 'simpledesk-infrastructure-'.$infrastructure->id;
        $this->assertSame($name, config('queue.connections.simpledesk-managed.connection'));
        $this->assertSame('runtime-secret', config("database.redis.{$name}.password"));
        $this->assertArrayNotHasKey('password', $queue->configuration);
        $this->assertArrayNotHasKey('host', $queue->configuration);
    }

    public function test_deployment_infrastructure_connection_reuses_laravel_redis_name(): void
    {
        config(['database.redis.queue-deployment' => ['host' => 'redis', 'port' => 6379, 'database' => 0]]);
        $infrastructure = InfrastructureConnection::factory()->create([
            'source' => InfrastructureConnectionSource::Deployment,
            'configuration' => ['connection_name' => 'queue-deployment'],
            'credentials' => [],
        ]);
        $queue = $this->queueConfiguration($infrastructure);
        $this->activate($queue);
        $this->app->make(QueueRuntimeConfigurator::class)->apply();

        $this->assertSame('queue-deployment', config('queue.connections.simpledesk-managed.connection'));
        $this->assertNull(config('database.redis.simpledesk-infrastructure-'.$infrastructure->id));
    }

    private function queueConfiguration(InfrastructureConnection $infrastructure): QueueDriverConfiguration
    {
        return QueueDriverConfiguration::query()->create([
            'name' => 'Managed Redis queue',
            'driver' => QueueDriverType::Redis,
            'configuration' => ['infrastructure_connection_id' => $infrastructure->id, 'retry_after' => 360, 'block_for' => 5, 'after_commit' => false],
            'is_enabled' => true,
        ]);
    }

    private function activate(QueueDriverConfiguration $queue): void
    {
        QueueDriverSettings::query()->create(['id' => 1, 'mode' => QueueConfigurationMode::Managed, 'active_configuration_id' => $queue->id]);
    }
}
