<?php

namespace App\Services\Admin\System\Search\Drivers;

use App\Contracts\Admin\System\Search\SearchDriverAdapter;
use App\Data\Admin\System\Search\SearchDriverDefinitionData;
use App\Data\Admin\System\Search\SearchHealthResultData;
use App\Data\Admin\System\Search\SearchRuntimeConfigurationData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Enums\Admin\System\SearchHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\SearchDriverConfiguration;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionRegistry;
use Illuminate\Validation\ValidationException;

abstract class ExternalSearchDriverAdapter implements SearchDriverAdapter
{
    public function __construct(private readonly InfrastructureConnectionRegistry $infrastructure) {}

    abstract protected function label(): string;

    abstract protected function infrastructureType(): InfrastructureConnectionType;

    abstract protected function connectivity(InfrastructureConnection $connection): array;

    public function definition(): SearchDriverDefinitionData
    {
        return new SearchDriverDefinitionData($this->type(), $this->label(), "Laravel Scout {$this->label()} engine.", $this->infrastructure->adapter($this->infrastructureType())->definition()->available, true);
    }

    public function validateAndNormalize(array $configuration, mixed $infrastructureConnectionId): array
    {
        if ($configuration !== []) {
            throw ValidationException::withMessages(['configuration' => 'Search provider profile configuration must be empty.']);
        }
        if (! is_numeric($infrastructureConnectionId) || (int) $infrastructureConnectionId < 1) {
            throw ValidationException::withMessages(['infrastructure_connection_id' => "{$this->label()} Search requires an infrastructure connection."]);
        }
        $this->connection((int) $infrastructureConnectionId);

        return ['configuration' => [], 'infrastructure_connection_id' => (int) $infrastructureConnectionId];
    }

    public function runtimeConfiguration(SearchDriverConfiguration $configuration): SearchRuntimeConfigurationData
    {
        $normalized = $this->validateAndNormalize($configuration->configuration ?? [], $configuration->infrastructure_connection_id);

        return new SearchRuntimeConfigurationData($this->type(), $this->connectivity($this->connection($normalized['infrastructure_connection_id'])));
    }

    public function test(SearchDriverConfiguration $configuration): SearchHealthResultData
    {
        $normalized = $this->validateAndNormalize($configuration->configuration ?? [], $configuration->infrastructure_connection_id);
        $result = $this->infrastructure->adapter($this->infrastructureType())->test($this->connection($normalized['infrastructure_connection_id']));
        $status = match ($result->status) {
            InfrastructureHealthStatus::Healthy => SearchHealthStatus::Healthy,
            InfrastructureHealthStatus::Degraded => SearchHealthStatus::Degraded,
            InfrastructureHealthStatus::Unhealthy => SearchHealthStatus::Unhealthy,
            InfrastructureHealthStatus::Unavailable => SearchHealthStatus::Unavailable,
            InfrastructureHealthStatus::Unknown => SearchHealthStatus::Unavailable,
        };

        return new SearchHealthResultData($status, $result->latencyMs, $result->message, $result->details);
    }

    protected function connection(int $id): InfrastructureConnection
    {
        $connection = InfrastructureConnection::withTrashed()->find($id);
        if (! $connection || $connection->trashed() || ! $connection->is_enabled || $connection->getRawOriginal('type') !== $this->infrastructureType()->value || $connection->getRawOriginal('source') !== InfrastructureConnectionSource::Managed->value) {
            throw ValidationException::withMessages(['infrastructure_connection_id' => "The selected {$this->label()} infrastructure connection is missing, archived, disabled, has the wrong type, or is not managed."]);
        }

        return $connection;
    }
}
