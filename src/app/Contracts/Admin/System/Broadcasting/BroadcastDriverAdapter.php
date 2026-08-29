<?php

namespace App\Contracts\Admin\System\Broadcasting;

use App\Data\Admin\System\Broadcasting\BroadcastDriverDefinitionData;
use App\Data\Admin\System\Broadcasting\BroadcastHealthResultData;
use App\Data\Admin\System\Broadcasting\BroadcastRuntimeConfigurationData;
use App\Enums\Admin\System\BroadcastDriverType;
use App\Models\Admin\System\BroadcastDriverConfiguration;

interface BroadcastDriverAdapter
{
    public function type(): BroadcastDriverType;

    public function definition(): BroadcastDriverDefinitionData;

    public function validateAndNormalize(array $configuration, mixed $infrastructureConnectionId): array;

    public function runtimeConfiguration(BroadcastDriverConfiguration $configuration): BroadcastRuntimeConfigurationData;

    public function test(BroadcastDriverConfiguration $configuration): BroadcastHealthResultData;
}
