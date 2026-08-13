<?php

namespace App\Contracts\Admin\System\Queues;

use App\Data\Admin\System\Queues\QueueDriverDefinitionData;
use App\Data\Admin\System\Queues\QueueHealthResultData;
use App\Data\Admin\System\Queues\QueueRuntimeConfigurationData;
use App\Enums\Admin\System\QueueDriverType;
use App\Models\Admin\System\QueueDriverConfiguration;

interface QueueDriverAdapter
{
    public function type(): QueueDriverType;

    public function definition(): QueueDriverDefinitionData;

    public function validateAndNormalize(array $configuration): array;

    public function runtimeConfiguration(QueueDriverConfiguration $configuration): QueueRuntimeConfigurationData;

    public function test(QueueDriverConfiguration $configuration): QueueHealthResultData;
}
