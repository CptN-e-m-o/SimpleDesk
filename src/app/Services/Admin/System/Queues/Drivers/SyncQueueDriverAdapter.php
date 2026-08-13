<?php

namespace App\Services\Admin\System\Queues\Drivers;

use App\Contracts\Admin\System\Queues\QueueDriverAdapter;
use App\Data\Admin\System\Queues\QueueDriverDefinitionData;
use App\Data\Admin\System\Queues\QueueRuntimeConfigurationData;
use App\Enums\Admin\System\QueueDriverType;
use App\Models\Admin\System\QueueDriverConfiguration;

class SyncQueueDriverAdapter implements QueueDriverAdapter
{
    public function type(): QueueDriverType
    {
        return QueueDriverType::Sync;
    }

    public function definition(): QueueDriverDefinitionData
    {
        return new QueueDriverDefinitionData(
            type: $this->type(),
            label: 'Sync',
            description:
            'Run queued work immediately in the current application process.',
            requiresInfrastructure: false,
            infrastructureType: null,
            recommendedForProduction: false,
        );
    }

    public function validateAndNormalize(
        array $configuration,
    ): array {
        return [];
    }

    public function runtimeConfiguration(
        QueueDriverConfiguration $configuration,
    ): QueueRuntimeConfigurationData {
        return new QueueRuntimeConfigurationData(
            queueConnection: [
                'driver' =>
                    'sync',
            ],
        );
    }
}
