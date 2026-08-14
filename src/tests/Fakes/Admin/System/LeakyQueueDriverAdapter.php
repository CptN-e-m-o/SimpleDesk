<?php

namespace Tests\Fakes\Admin\System;

use App\Contracts\Admin\System\Queues\QueueDriverAdapter;
use App\Data\Admin\System\Queues\QueueDriverDefinitionData;
use App\Data\Admin\System\Queues\QueueHealthResultData;
use App\Data\Admin\System\Queues\QueueRuntimeConfigurationData;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\QueueDriverType;
use App\Enums\Admin\System\QueueHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;

class LeakyQueueDriverAdapter implements QueueDriverAdapter
{
    public function type(): QueueDriverType
    {
        return QueueDriverType::Redis;
    }

    public function definition(): QueueDriverDefinitionData
    {
        return new QueueDriverDefinitionData(
            type: $this->type(),

            label: 'Leaky Redis',

            description: 'Test-only queue adapter.',

            requiresInfrastructure: true,

            infrastructureType: InfrastructureConnectionType::Redis->value,

            recommendedForProduction: false,
        );
    }

    public function validateAndNormalize(
        array $configuration,
    ): array {
        return $configuration;
    }

    public function runtimeConfiguration(
        QueueDriverConfiguration $configuration,
    ): QueueRuntimeConfigurationData {
        return new QueueRuntimeConfigurationData(
            queueConnection: [
                'driver' => 'sync',
            ],
        );
    }

    public function test(
        QueueDriverConfiguration $configuration,
    ): QueueHealthResultData {
        $connection =
            InfrastructureConnection::withTrashed()
                ->findOrFail(
                    (int) $configuration
                        ->configuration[
                    'infrastructure_connection_id'
                    ],
                );

        $password =
            (string) (
                $connection
                    ->secrets()[
                'password'
                ]
                ?? ''
            );

        return new QueueHealthResultData(
            status: QueueHealthStatus::Healthy,

            latencyMs: 4,

            message: "Connected using {$password}.",

            details: [
                'diagnostic_dsn' => "redis://simpledesk:{$password}@redis.internal:6379/0",

                'nested' => [
                    'password_echo' => $password,
                ],
            ],
        );
    }
}
