<?php

namespace Tests\Feature\Admin\System\Queues;

use App\Enums\Admin\System\QueueConfigurationMode;
use App\Enums\Admin\System\QueueDriverType;
use App\Exceptions\Admin\System\Queues\InvalidManagedQueueConfigurationException;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\QueueDriverSettings;
use App\Services\Admin\System\Queues\QueueRuntimeConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueRuntimeConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_settings_row_keeps_existing_default_untouched(): void
    {
        config(['queue.default' => 'existing']);
        $this->configurator()->apply();
        $this->assertSame('existing', config('queue.default'));
    }

    public function test_deployment_mode_keeps_existing_default_untouched(): void
    {
        QueueDriverSettings::query()->create(['id' => 1, 'mode' => QueueConfigurationMode::Deployment]);
        config(['queue.default' => 'redis']);
        $this->configurator()->apply();
        $this->assertSame('redis', config('queue.default'));
    }

    public function test_managed_database_registers_runtime_connection_and_default(): void
    {
        $configuration = $this->databaseConfiguration();
        $this->managedSettings($configuration);
        $this->configurator()->apply();

        $this->assertSame('simpledesk-managed', config('queue.default'));
        $this->assertSame('database', config('queue.connections.simpledesk-managed.driver'));
        $this->assertSame(config('database.default'), config('queue.connections.simpledesk-managed.connection'));
        $this->assertSame(360, config('queue.connections.simpledesk-managed.retry_after'));
    }

    public function test_disabled_active_configuration_fails_loudly(): void
    {
        $configuration = $this->databaseConfiguration(['is_enabled' => false]);
        $this->managedSettings($configuration);
        $this->expectException(InvalidManagedQueueConfigurationException::class);
        $this->expectExceptionMessage('disabled');
        $this->configurator()->apply();
    }

    public function test_missing_active_configuration_fails_loudly(): void
    {
        QueueDriverSettings::query()->create(['id' => 1, 'mode' => QueueConfigurationMode::Managed]);
        $this->expectException(InvalidManagedQueueConfigurationException::class);
        $this->expectExceptionMessage('requires an active');
        $this->configurator()->apply();
    }

    public function test_archived_active_configuration_fails_loudly(): void
    {
        $configuration = $this->databaseConfiguration();
        $this->managedSettings($configuration);
        $configuration->delete();
        $this->expectException(InvalidManagedQueueConfigurationException::class);
        $this->expectExceptionMessage('archived');
        $this->configurator()->apply();
    }

    private function databaseConfiguration(array $attributes = []): QueueDriverConfiguration
    {
        return QueueDriverConfiguration::query()->create([
            'name' => 'Managed database',
            'driver' => QueueDriverType::Database,
            'configuration' => ['database_connection' => config('database.default'), 'retry_after' => 360, 'after_commit' => false],
            'is_enabled' => true,
            ...$attributes,
        ]);
    }

    private function managedSettings(QueueDriverConfiguration $configuration): void
    {
        QueueDriverSettings::query()->create(['id' => 1, 'mode' => QueueConfigurationMode::Managed, 'active_configuration_id' => $configuration->id]);
    }

    private function configurator(): QueueRuntimeConfigurator
    {
        return $this->app->make(QueueRuntimeConfigurator::class);
    }
}
