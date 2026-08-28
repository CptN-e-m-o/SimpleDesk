<?php

namespace Tests\Fakes\Admin\System;

use App\Contracts\Admin\System\Storage\StorageDriverAdapter;
use App\Data\Admin\System\Storage\StorageDriverDefinitionData;
use App\Data\Admin\System\Storage\StorageHealthResultData;
use App\Data\Admin\System\Storage\StorageRuntimeConfigurationData;
use App\Enums\Admin\System\StorageDriverType;
use App\Enums\Admin\System\StorageHealthStatus;
use App\Models\Admin\System\StorageDriverConfiguration;

class FakeStorageDriverAdapter implements StorageDriverAdapter
{
    public $onTest = null;

    public function __construct(private readonly StorageDriverType $driver = StorageDriverType::Local, public StorageHealthStatus $health = StorageHealthStatus::Healthy) {}

    public function type(): StorageDriverType
    {
        return $this->driver;
    }

    public function definition(): StorageDriverDefinitionData
    {
        return new StorageDriverDefinitionData($this->driver, 'Fake', true, $this->driver !== StorageDriverType::Local);
    }

    public function validateAndNormalize(array $configuration, mixed $infrastructureConnectionId): array
    {
        return ['configuration' => $configuration, 'infrastructure_connection_id' => $infrastructureConnectionId];
    }

    public function runtimeConfiguration(StorageDriverConfiguration $configuration): StorageRuntimeConfigurationData
    {
        return new StorageRuntimeConfigurationData($this->driver, ['driver' => 'local', 'root' => storage_path('app/private')]);
    }

    public function test(StorageDriverConfiguration $configuration): StorageHealthResultData
    {
        if ($this->onTest) {
            ($this->onTest)();
        }

        return new StorageHealthResultData($this->health, 1, 'Fake health.');
    }
}
