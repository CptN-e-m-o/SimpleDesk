<?php

namespace App\Services\Admin\System\Broadcasting\Drivers;

use App\Contracts\Admin\System\Broadcasting\BroadcastDriverAdapter;
use App\Data\Admin\System\Broadcasting\BroadcastDriverDefinitionData;
use App\Data\Admin\System\Broadcasting\BroadcastHealthResultData;
use App\Data\Admin\System\Broadcasting\BroadcastRuntimeConfigurationData;
use App\Enums\Admin\System\BroadcastDriverType;
use App\Enums\Admin\System\BroadcastHealthStatus;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Infrastructure\Connections\PusherProtocolInfrastructureConnectionAdapter;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionRegistry;
use Illuminate\Validation\ValidationException;

abstract class PusherProtocolBroadcastDriverAdapter implements BroadcastDriverAdapter
{
    public function __construct(
        private readonly InfrastructureConnectionRegistry $infrastructure,
    ) {}

    abstract public function type(): BroadcastDriverType;

    abstract protected function infrastructureType(): InfrastructureConnectionType;

    abstract protected function label(): string;

    public function definition(): BroadcastDriverDefinitionData
    {
        return new BroadcastDriverDefinitionData(
            $this->type(),
            $this->label(),
            'Outbound events are published to an existing '.$this->label().' endpoint.',
            class_exists('Pusher\\Pusher'),
        );
    }

    public function validateAndNormalize(array $configuration, mixed $infrastructureConnectionId): array
    {
        if ($configuration !== []) {
            throw ValidationException::withMessages([
                'configuration' => 'Broadcast profiles cannot contain provider connection settings or credentials.',
            ]);
        }

        $id = filter_var(
            $infrastructureConnectionId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        $connection = $id === false
            ? null
            : InfrastructureConnection::withTrashed()->find($id);

        if (
            ! $connection
            || $connection->trashed()
            || ! $connection->is_enabled
            || $connection->type !== $this->infrastructureType()
        ) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'An enabled, available '.$this->label().' infrastructure connection is required.',
            ]);
        }

        $adapter = $this->infrastructure->adapter($connection->type);

        if (! $adapter instanceof PusherProtocolInfrastructureConnectionAdapter) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'The selected infrastructure connection cannot provide a Pusher-protocol broadcaster.',
            ]);
        }

        $adapter->publisherConnection($connection, $this->type()->value);

        return [
            'configuration' => [],
            'infrastructure_connection_id' => $connection->id,
        ];
    }

    public function runtimeConfiguration(BroadcastDriverConfiguration $configuration): BroadcastRuntimeConfigurationData
    {
        $connection = $this->connection($configuration);
        $adapter = $this->infrastructure->adapter($connection->type);

        if (! $adapter instanceof PusherProtocolInfrastructureConnectionAdapter) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'The profile infrastructure adapter is incompatible with this broadcaster.',
            ]);
        }

        $runtime = $adapter->publisherConnection(
            $connection,
            $this->type()->value,
        );

        return new BroadcastRuntimeConfigurationData(
            $runtime,
            [
                'broadcaster' => $this->type()->value,
                ...$adapter->safeClient($connection),
            ],
        );
    }

    public function test(BroadcastDriverConfiguration $configuration): BroadcastHealthResultData
    {
        $connection = $this->connection($configuration);
        $result = $this->infrastructure->adapter($connection->type)->test($connection);

        $status = match ($result->status) {
            InfrastructureHealthStatus::Healthy => BroadcastHealthStatus::Healthy,
            InfrastructureHealthStatus::Degraded => BroadcastHealthStatus::Degraded,
            InfrastructureHealthStatus::Unhealthy => BroadcastHealthStatus::Unhealthy,
            InfrastructureHealthStatus::Unavailable,
            InfrastructureHealthStatus::Unknown => BroadcastHealthStatus::Unavailable,
        };

        return new BroadcastHealthResultData(
            $status,
            $result->latencyMs ?? 0,
            $result->message ?? 'Provider health is unknown.',
            $result->details,
        );
    }

    private function connection(BroadcastDriverConfiguration $configuration): InfrastructureConnection
    {
        $connection = InfrastructureConnection::withTrashed()->find(
            $configuration->infrastructure_connection_id,
        );

        if (
            ! $connection
            || $connection->trashed()
            || ! $connection->is_enabled
            || $connection->type !== $this->infrastructureType()
        ) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'The profile infrastructure connection is unavailable.',
            ]);
        }

        return $connection;
    }
}
