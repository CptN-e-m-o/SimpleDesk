<?php

namespace App\Services\Admin\System\Queues;

use App\Enums\Admin\System\QueueConfigurationMode;
use App\Exceptions\Admin\System\Queues\InvalidManagedQueueConfigurationException;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\QueueDriverSettings;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QueueRuntimeConfigurator
{
    public function __construct(
        private readonly QueueDriverRegistry $registry,
    ) {}

    public function apply(): void
    {
        try {
            $tablesExist = Schema::hasTable('queue_driver_settings')
                && Schema::hasTable('queue_driver_configurations');
        } catch (Throwable $exception) {
            if (! app()->runningInConsole()) {
                throw $exception;
            }

            return;
        }

        if (! $tablesExist) {
            return;
        }

        $settings = QueueDriverSettings::query()->find(QueueDriverSettings::SINGLETON_ID);

        if (! $settings || $settings->mode === QueueConfigurationMode::Deployment) {
            return;
        }

        if (! $settings->active_configuration_id) {
            throw new InvalidManagedQueueConfigurationException('Managed queue mode requires an active queue driver configuration.');
        }

        $configuration = QueueDriverConfiguration::withTrashed()->find($settings->active_configuration_id);

        if (! $configuration) {
            throw new InvalidManagedQueueConfigurationException('The active managed queue driver configuration does not exist.');
        }

        if ($configuration->trashed()) {
            throw new InvalidManagedQueueConfigurationException('The active managed queue driver configuration is archived.');
        }

        if (! $configuration->is_enabled) {
            throw new InvalidManagedQueueConfigurationException('The active managed queue driver configuration is disabled.');
        }

        try {
            $runtime = $this->registry->adapter($configuration->driver)->runtimeConfiguration($configuration);
        } catch (InvalidManagedQueueConfigurationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidManagedQueueConfigurationException('Unable to configure the active managed queue driver: '.$exception->getMessage(), previous: $exception);
        }

        foreach ($runtime->redisConnections as $name => $redisConfiguration) {
            config(["database.redis.{$name}" => $redisConfiguration]);
        }

        $connectionName = (string) config('simpledesk-queues.runtime.connection_name', 'simpledesk-managed');
        config([
            "queue.connections.{$connectionName}" => $runtime->queueConnection,
            'queue.default' => $connectionName,
        ]);
    }
}
