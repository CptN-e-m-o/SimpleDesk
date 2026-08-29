<?php

namespace App\Contracts\Admin\System\Storage;

use App\Data\Admin\System\Storage\StorageDriverDefinitionData;
use App\Data\Admin\System\Storage\StorageHealthResultData;
use App\Data\Admin\System\Storage\StorageRuntimeConfigurationData;
use App\Enums\Admin\System\StorageDriverType;
use App\Models\Admin\System\StorageDriverConfiguration;

interface StorageDriverAdapter
{
    public function type(): StorageDriverType;

    public function definition(): StorageDriverDefinitionData;

    public function validateAndNormalize(array $configuration, mixed $infrastructureConnectionId): array;

    public function runtimeConfiguration(StorageDriverConfiguration $configuration): StorageRuntimeConfigurationData;

    public function test(StorageDriverConfiguration $configuration): StorageHealthResultData;
}
