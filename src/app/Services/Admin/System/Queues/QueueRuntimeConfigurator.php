<?php

namespace App\Services\Admin\System\Queues;

use App\Enums\Admin\System\QueueConfigurationMode;
use App\Exceptions\Admin\System\Queues\InvalidManagedQueueConfigurationException;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\QueueDriverSettings;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureRuntimeConnectionRegistrar;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QueueRuntimeConfigurator
{
    public function __construct(
        private readonly QueueDriverRegistry $registry,
        private readonly RedisInfrastructureRuntimeConnectionRegistrar $redisRegistrar,
        private readonly QueueRuntimeBootstrapPolicy $bootstrapPolicy,
    ) {}

    public function apply(): void
    {
        try {
            $tablesExist =
                Schema::hasTable(
                    'queue_driver_settings',
                )
                && Schema::hasTable(
                    'queue_driver_configurations',
                );
        } catch (Throwable $exception) {
            if (
                $this
                    ->bootstrapPolicy
                    ->maySkipDatabaseInspectionFailure()
            ) {
                return;
            }

            throw new InvalidManagedQueueConfigurationException(
                'Unable to determine queue configuration ownership because the queue settings tables could not be inspected.',
                previous: $exception,
            );
        }

        if (! $tablesExist) {
            return;
        }

        $settings =
            QueueDriverSettings::query()
                ->find(
                    QueueDriverSettings::SINGLETON_ID,
                );

        /*
         * No settings row means SimpleDesk has never
         * taken ownership of queue configuration.
         */
        if (! $settings) {
            return;
        }

        if (
            $settings->mode ===
            QueueConfigurationMode::Deployment
        ) {
            return;
        }

        if (
            ! $settings
                ->active_configuration_id
        ) {
            throw new InvalidManagedQueueConfigurationException(
                'Managed queue mode requires an active queue driver configuration.',
            );
        }

        $configuration =
            QueueDriverConfiguration::withTrashed()
                ->find(
                    $settings
                        ->active_configuration_id,
                );

        if (! $configuration) {
            throw new InvalidManagedQueueConfigurationException(
                'The active managed queue driver configuration does not exist.',
            );
        }

        if ($configuration->trashed()) {
            throw new InvalidManagedQueueConfigurationException(
                'The active managed queue driver configuration is archived.',
            );
        }

        if (! $configuration->is_enabled) {
            throw new InvalidManagedQueueConfigurationException(
                'The active managed queue driver configuration is disabled.',
            );
        }

        try {
            $runtime =
                $this
                    ->registry
                    ->adapter(
                        $configuration->driver,
                    )
                    ->runtimeConfiguration(
                        $configuration,
                    );
        } catch (
            InvalidManagedQueueConfigurationException $exception
        ) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidManagedQueueConfigurationException(
                'Unable to configure the active managed queue driver: '
                .$exception->getMessage(),
                previous: $exception,
            );
        }

        $this
            ->redisRegistrar
            ->registerMany(
                $runtime
                    ->redisConnections,
            );

        $connectionName =
            trim(
                (string) config(
                    'simpledesk-queues.runtime.connection_name',
                    'simpledesk-managed',
                ),
            );

        if ($connectionName === '') {
            throw new InvalidManagedQueueConfigurationException(
                'Managed queue runtime connection name cannot be empty.',
            );
        }

        config()->set(
            "queue.connections.{$connectionName}",
            $runtime
                ->queueConnection,
        );

        config()->set(
            'queue.default',
            $connectionName,
        );
    }
}
