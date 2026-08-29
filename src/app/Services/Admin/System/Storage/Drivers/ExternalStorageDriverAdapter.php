<?php

namespace App\Services\Admin\System\Storage\Drivers;

use App\Contracts\Admin\System\Storage\StorageDriverAdapter;
use App\Data\Admin\System\Storage\StorageDriverDefinitionData;
use App\Data\Admin\System\Storage\StorageHealthResultData;
use App\Data\Admin\System\Storage\StorageRuntimeConfigurationData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\StorageDriverConfiguration;
use App\Services\Admin\System\Storage\StorageFilesystemFactory;
use App\Services\Admin\System\Storage\StorageFilesystemHealthProbe;
use App\Services\Admin\System\Storage\StoragePrefixNormalizer;
use Illuminate\Validation\ValidationException;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

abstract class ExternalStorageDriverAdapter implements StorageDriverAdapter
{
    public function __construct(private readonly StoragePrefixNormalizer $prefixes, private readonly StorageFilesystemFactory $factory, private readonly StorageFilesystemHealthProbe $probe) {}

    abstract protected function label(): string;

    abstract protected function infrastructureType(): InfrastructureConnectionType;

    abstract protected function disk(InfrastructureConnection $connection, string $prefix): array;

    public function definition(): StorageDriverDefinitionData
    {
        return new StorageDriverDefinitionData($this->type(), $this->label(), class_exists(AwsS3V3Adapter::class), true, $this->infrastructureType()->value, class_exists(AwsS3V3Adapter::class) ? null : 'The S3 Flysystem adapter is not installed.');
    }

    public function validateAndNormalize(array $configuration, mixed $infrastructureConnectionId): array
    {
        if (array_diff(array_keys($configuration), ['prefix']) !== []) {
            throw ValidationException::withMessages(['configuration' => 'Storage profile contains unsupported configuration.']);
        }
        if (! is_numeric($infrastructureConnectionId) || (int) $infrastructureConnectionId < 1) {
            throw ValidationException::withMessages(['infrastructure_connection_id' => "{$this->label()} requires an infrastructure connection."]);
        }
        $this->connection((int) $infrastructureConnectionId);
        $prefix = $this->prefixes->normalize($configuration['prefix'] ?? null);

        return ['configuration' => $prefix === '' ? [] : ['prefix' => $prefix], 'infrastructure_connection_id' => (int) $infrastructureConnectionId];
    }

    public function runtimeConfiguration(StorageDriverConfiguration $configuration): StorageRuntimeConfigurationData
    {
        $normalized = $this->validateAndNormalize($configuration->configuration ?? [], $configuration->infrastructure_connection_id);

        return new StorageRuntimeConfigurationData($this->type(), $this->disk($this->connection($normalized['infrastructure_connection_id']), $normalized['configuration']['prefix'] ?? ''));
    }

    public function test(StorageDriverConfiguration $configuration): StorageHealthResultData
    {
        return $this->probe->test(
            $this->factory->buildForHealth(
                $this->runtimeConfiguration($configuration)->disk,
            ),
        );
    }

    protected function baseDisk(InfrastructureConnection $connection, string $prefix): array
    {
        $value = $connection->getAttribute('configuration');
        $config = is_array($value) ? $value : [];
        $secrets = $connection->secrets();

        return ['driver' => 's3', 'key' => $secrets['access_key_id'] ?? null, 'secret' => $secrets['secret_access_key'] ?? null, 'region' => $config['region'] ?? null, 'bucket' => $config['bucket'] ?? null, 'root' => $prefix, 'visibility' => 'private', 'throw' => false, 'report' => false];
    }

    protected function connection(int $id): InfrastructureConnection
    {
        $connection = InfrastructureConnection::withTrashed()->find($id);
        if (! $connection || $connection->trashed() || ! $connection->is_enabled || $connection->getRawOriginal('type') !== $this->infrastructureType()->value || $connection->getRawOriginal('source') !== InfrastructureConnectionSource::Managed->value) {
            throw ValidationException::withMessages(['infrastructure_connection_id' => 'The selected object-storage connection is missing, archived, disabled, has the wrong type, or is not managed.']);
        }

        return $connection;
    }
}
