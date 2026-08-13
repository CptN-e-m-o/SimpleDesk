<?php

namespace App\Services\Admin\System\Queues\Drivers;

use App\Contracts\Admin\System\Queues\QueueDriverAdapter;
use App\Data\Admin\System\Queues\QueueDriverDefinitionData;
use App\Data\Admin\System\Queues\QueueRuntimeConfigurationData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\QueueDriverType;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureRuntimeConfigurationFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RedisQueueDriverAdapter implements QueueDriverAdapter
{
    public function __construct(
        private readonly RedisInfrastructureRuntimeConfigurationFactory $runtimeFactory,
    ) {}

    public function type(): QueueDriverType
    {
        return QueueDriverType::Redis;
    }

    public function definition(): QueueDriverDefinitionData
    {
        return new QueueDriverDefinitionData($this->type(), 'Redis', 'Use an enabled Redis infrastructure connection for queued jobs.');
    }

    public function validateAndNormalize(array $configuration): array
    {
        $validated = Validator::make($configuration, [
            'infrastructure_connection_id' => [
                'required',
                'integer',
                Rule::exists('infrastructure_connections', 'id')->where(fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->where('is_enabled', true)
                    ->where('type', InfrastructureConnectionType::Redis->value)),
            ],
            'retry_after' => ['required', 'integer', 'min:1'],
            'block_for' => ['nullable', 'integer', 'min:0'],
            'after_commit' => ['required', 'boolean'],
        ])->validate();

        return [
            'infrastructure_connection_id' => (int) $validated['infrastructure_connection_id'],
            'retry_after' => (int) $validated['retry_after'],
            'block_for' => isset($validated['block_for']) ? (int) $validated['block_for'] : null,
            'after_commit' => (bool) $validated['after_commit'],
        ];
    }

    public function runtimeConfiguration(QueueDriverConfiguration $configuration): QueueRuntimeConfigurationData
    {
        $values = $this->validateAndNormalize($configuration->configuration ?? []);
        $connection = InfrastructureConnection::query()->find($values['infrastructure_connection_id']);

        if (! $connection) {
            throw ValidationException::withMessages(['configuration.infrastructure_connection_id' => 'The selected Redis infrastructure connection is unavailable.']);
        }

        $redisConnections = [];

        if ($connection->source === InfrastructureConnectionSource::Managed) {
            $redisConnectionName = 'simpledesk-infrastructure-'.$connection->id;
            $redisConnections[$redisConnectionName] = $this->runtimeFactory->make($connection);
        } else {
            $redisConnectionName = (string) ($connection->configuration['connection_name'] ?? '');

            if ($redisConnectionName === '' || ! is_array(config("database.redis.{$redisConnectionName}"))) {
                throw ValidationException::withMessages(['configuration.infrastructure_connection_id' => 'The deployment Redis connection no longer exists.']);
            }
        }

        return new QueueRuntimeConfigurationData(
            queueConnection: [
                'driver' => 'redis',
                'connection' => $redisConnectionName,
                'queue' => 'default',
                'retry_after' => $values['retry_after'],
                'block_for' => $values['block_for'],
                'after_commit' => $values['after_commit'],
            ],
            redisConnections: $redisConnections,
        );
    }
}
