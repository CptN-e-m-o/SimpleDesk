<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Contracts\Admin\System\Infrastructure\InfrastructureConnectionAdapter;
use App\Data\Admin\System\Infrastructure\InfrastructureConnectionDefinitionData;
use App\Data\Admin\System\Infrastructure\InfrastructureHealthResultData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Enums\Admin\System\StorageHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Storage\StorageFilesystemFactory;
use App\Services\Admin\System\Storage\StorageFilesystemHealthProbe;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

abstract class AbstractS3InfrastructureConnectionAdapter implements InfrastructureConnectionAdapter
{
    public function __construct(private readonly StorageFilesystemFactory $factory, private readonly StorageFilesystemHealthProbe $probe) {}

    abstract protected function label(): string;

    abstract protected function configurationRules(): array;

    abstract protected function normalizeConfiguration(array $configuration): array;

    abstract protected function disk(array $configuration, array $credentials): array;

    public function definition(): InfrastructureConnectionDefinitionData
    {
        return new InfrastructureConnectionDefinitionData($this->type(), $this->label(), "Managed {$this->label()} bucket connection.", [InfrastructureConnectionSource::Managed], class_exists(AwsS3V3Adapter::class));
    }

    public function validateAndNormalize(array $configuration, array $credentials, string $source): array
    {
        $validated = Validator::make(['configuration' => $configuration, 'credentials' => $credentials, 'source' => $source], [
            'source' => ['required', Rule::in([InfrastructureConnectionSource::Managed->value])],
            ...$this->configurationRules(),
            'credentials.access_key_id' => ['required', 'string', 'max:512'],
            'credentials.secret_access_key' => ['required', 'string', 'max:4096'],
        ])->validate();

        return ['configuration' => $this->normalizeConfiguration($validated['configuration']), 'credentials' => ['access_key_id' => $validated['credentials']['access_key_id'], 'secret_access_key' => $validated['credentials']['secret_access_key']]];
    }

    public function secretFields(): array
    {
        return ['access_key_id', 'secret_access_key'];
    }

    public function publicRepresentation(InfrastructureConnection $connection): array
    {
        $credentials = $connection->secrets();

        return ['configuration' => $connection->configuration ?? [], 'credential_flags' => ['access_key_id_configured' => isset($credentials['access_key_id']), 'secret_access_key_configured' => isset($credentials['secret_access_key'])]];
    }

    public function test(InfrastructureConnection $connection): InfrastructureHealthResultData
    {
        $normalized = $this->validateAndNormalize($connection->configuration ?? [], $connection->secrets(), (string) $connection->getRawOriginal('source'));
        $result = $this->probe->test($this->factory->build($this->disk($normalized['configuration'], $normalized['credentials'])));
        $status = match ($result->status) {
            StorageHealthStatus::Healthy => InfrastructureHealthStatus::Healthy,
            StorageHealthStatus::Degraded => InfrastructureHealthStatus::Degraded,
            StorageHealthStatus::Unhealthy => InfrastructureHealthStatus::Unhealthy,
            StorageHealthStatus::Unavailable => InfrastructureHealthStatus::Unavailable,
        };

        return new InfrastructureHealthResultData($status, $result->latencyMs, $result->message, ['operation' => 'random_write_read_delete']);
    }

    protected function baseDisk(array $configuration, array $credentials): array
    {
        return ['driver' => 's3', 'key' => $credentials['access_key_id'], 'secret' => $credentials['secret_access_key'], 'region' => $configuration['region'], 'bucket' => $configuration['bucket'], 'visibility' => 'private', 'throw' => false, 'report' => false];
    }
}
