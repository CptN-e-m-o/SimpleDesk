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
        return new QueueDriverDefinitionData($this->type(), 'Sync', 'Run queued work in the current request process.', false);
    }

    public function validateAndNormalize(array $configuration): array
    {
        return [];
    }

    public function runtimeConfiguration(QueueDriverConfiguration $configuration): QueueRuntimeConfigurationData
    {
        return new QueueRuntimeConfigurationData(['driver' => 'sync']);
    }
}
