<?php

namespace App\Services\Admin\System\Storage\Drivers;

use App\Contracts\Admin\System\Storage\StorageDriverAdapter;
use App\Data\Admin\System\Storage\StorageDriverDefinitionData;
use App\Data\Admin\System\Storage\StorageHealthResultData;
use App\Data\Admin\System\Storage\StorageRuntimeConfigurationData;
use App\Enums\Admin\System\StorageDriverType;
use App\Models\Admin\System\StorageDriverConfiguration;
use App\Services\Admin\System\Storage\StorageFilesystemFactory;
use App\Services\Admin\System\Storage\StorageFilesystemHealthProbe;
use Illuminate\Validation\ValidationException;

class LocalStorageDriverAdapter implements StorageDriverAdapter
{
    public function __construct(private readonly StorageFilesystemFactory $factory, private readonly StorageFilesystemHealthProbe $probe) {}

    public function type(): StorageDriverType
    {
        return StorageDriverType::Local;
    }

    public function definition(): StorageDriverDefinitionData
    {
        return new StorageDriverDefinitionData($this->type(), 'Local private storage', true, false);
    }

    public function validateAndNormalize(array $configuration, mixed $infrastructureConnectionId): array
    {
        if ($configuration !== []) {
            throw ValidationException::withMessages(['configuration' => 'Local Storage configuration must be empty.']);
        }
        if ($infrastructureConnectionId !== null && $infrastructureConnectionId !== '') {
            throw ValidationException::withMessages(['infrastructure_connection_id' => 'Local Storage does not use an infrastructure connection.']);
        }

        return ['configuration' => [], 'infrastructure_connection_id' => null];
    }

    public function runtimeConfiguration(StorageDriverConfiguration $configuration): StorageRuntimeConfigurationData
    {
        $this->validateAndNormalize($configuration->configuration ?? [], $configuration->infrastructure_connection_id);

        return new StorageRuntimeConfigurationData($this->type(), ['driver' => 'local', 'root' => config('simpledesk-storage.local.root'), 'visibility' => 'private', 'throw' => false, 'report' => false]);
    }

    public function test(StorageDriverConfiguration $configuration): StorageHealthResultData
    {
        return $this->probe->test(
            $this->factory->buildForHealth(
                $this->runtimeConfiguration($configuration)->disk,
            ),
        );
    }
}
