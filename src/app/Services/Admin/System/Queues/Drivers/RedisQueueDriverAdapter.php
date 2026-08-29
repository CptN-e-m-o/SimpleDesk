<?php

namespace App\Services\Admin\System\Queues\Drivers;

use App\Contracts\Admin\System\Queues\QueueDriverAdapter;
use App\Data\Admin\System\Queues\QueueDriverDefinitionData;
use App\Data\Admin\System\Queues\QueueHealthResultData;
use App\Data\Admin\System\Queues\QueueRuntimeConfigurationData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Enums\Admin\System\QueueDriverType;
use App\Enums\Admin\System\QueueHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureRuntimeConfigurationFactory;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionRegistry;
use App\Services\Admin\System\Queues\QueueSafetyPolicy;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RedisQueueDriverAdapter implements QueueDriverAdapter
{
    public function __construct(
        private readonly RedisInfrastructureRuntimeConfigurationFactory $runtimeFactory,
        private readonly QueueSafetyPolicy $safety,
        private readonly InfrastructureConnectionRegistry $infrastructureRegistry,
    ) {}

    public function type(): QueueDriverType
    {
        return QueueDriverType::Redis;
    }

    public function definition(): QueueDriverDefinitionData
    {
        return new QueueDriverDefinitionData(
            type: $this->type(),
            label: 'Redis',
            description: 'Use an enabled Redis infrastructure connection for queued jobs.',
            requiresInfrastructure: true,
            infrastructureType: InfrastructureConnectionType::Redis->value,
            recommendedForProduction: true,
        );
    }

    public function validateAndNormalize(
        array $configuration,
    ): array {
        $blockFor = array_key_exists(
            'block_for',
            $configuration,
        )
            ? $configuration['block_for']
            : config(
                'simpledesk-queues.defaults.redis_block_for',
                5,
            );

        $input = [
            'retry_after' => $configuration[
                'retry_after'
                ] ?? config(
                    'simpledesk-queues.defaults.retry_after',
                    360,
                ),

            'block_for' => $blockFor,

            'after_commit' => $configuration[
                'after_commit'
                ] ?? config(
                    'simpledesk-queues.defaults.after_commit',
                    false,
                ),
        ];

        $validated = Validator::make(
            $input,
            [
                'retry_after' => $this
                    ->safety
                    ->retryAfterRules(),

                'block_for' => [
                    'nullable',
                    'integer',
                    'between:1,60',
                ],

                'after_commit' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                ...$this
                    ->safety
                    ->retryAfterMessages(),

                'block_for.between' => 'Redis block for must be null or between 1 and 60 seconds. A value of 0 is not allowed.',
            ],
        )->validate();

        return [
            'retry_after' => (int) $validated[
            'retry_after'
            ],

            'block_for' => $validated[
            'block_for'
            ] === null
                ? null
                : (int) $validated[
                'block_for'
                ],

            'after_commit' => (bool) $validated[
            'after_commit'
            ],
        ];
    }

    public function runtimeConfiguration(
        QueueDriverConfiguration $configuration,
    ): QueueRuntimeConfigurationData {
        $values = $this->validateAndNormalize(
            $configuration->configuration ?? [],
        );

        $connection = $this->resolveConnection(
            $configuration,
        );

        $redisConnections = [];

        if (
            $connection->source ===
            InfrastructureConnectionSource::Managed
        ) {
            $redisConnectionName =
                'simpledesk-infrastructure-'
                .$connection->id;

            $redisConnections[
            $redisConnectionName
            ] = $this
                ->runtimeFactory
                ->make(
                    $connection,
                );
        } else {
            $redisConnectionName = trim(
                (string) (
                    $connection
                        ->configuration[
                    'connection_name'
                    ]
                    ?? ''
                ),
            );

            if (
                $redisConnectionName === ''
                || ! is_array(
                    config(
                        "database.redis.{$redisConnectionName}",
                    ),
                )
            ) {
                throw ValidationException::withMessages([
                    'infrastructure_connection_id' => 'The deployment Redis connection referenced by this infrastructure connection no longer exists.',
                ]);
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

    public function test(
        QueueDriverConfiguration $configuration,
    ): QueueHealthResultData {
        $this->validateAndNormalize(
            $configuration->configuration ?? [],
        );

        $connection = $this->resolveConnection(
            $configuration,
        );

        $result = $this->infrastructureRegistry
            ->adapter($connection->type)
            ->test($connection);

        $status = match ($result->status) {
            InfrastructureHealthStatus::Healthy => QueueHealthStatus::Healthy,
            InfrastructureHealthStatus::Degraded => QueueHealthStatus::Degraded,
            InfrastructureHealthStatus::Unhealthy => QueueHealthStatus::Unhealthy,
            default => QueueHealthStatus::Unavailable,
        };

        return new QueueHealthResultData(
            status: $status,
            latencyMs: $result->latencyMs,
            message: $this->safeHealthMessage(
                $result->status,
                $result->message,
            ),
            details: [
                'infrastructure_connection_id' => $connection->id,
                'source' => $connection->source->value,
                'infrastructure_status' => $result->status->value,
            ],
        );
    }

    private function safeHealthMessage(
        InfrastructureHealthStatus $status,
        ?string $message,
    ): string {
        if ($status === InfrastructureHealthStatus::Healthy) {
            return 'Redis queue connection verified successfully.';
        }

        $message = strtolower(trim((string) $message));

        foreach ([
            'noauth',
            'wrongpass',
            'authentication',
            'auth failed',
            'invalid password',
        ] as $needle) {
            if (str_contains($message, $needle)) {
                return 'Redis authentication failed.';
            }
        }

        foreach ([
            'connection refused',
            'timed out',
            'timeout',
            'getaddrinfo',
            'name or service not known',
            'no route to host',
            'network is unreachable',
        ] as $needle) {
            if (str_contains($message, $needle)) {
                return 'Redis server could not be reached.';
            }
        }

        if (str_contains($message, 'write/read verification failed')) {
            return 'Redis is reachable, but write/read verification failed.';
        }

        if (str_contains($message, 'delete verification failed')) {
            return 'Redis is reachable, but cleanup verification failed.';
        }

        return match ($status) {
            InfrastructureHealthStatus::Degraded => 'Redis queue connection is reachable but degraded.',
            InfrastructureHealthStatus::Unhealthy => 'Redis queue connection could not be verified.',
            default => 'Redis queue connectivity test is unavailable.',
        };
    }

    private function resolveConnection(
        QueueDriverConfiguration $configuration,
    ): InfrastructureConnection {
        $connectionId =
            $configuration->infrastructure_connection_id;

        if (! $connectionId) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'Redis queue configuration requires an infrastructure connection.',
            ]);
        }

        $connection = InfrastructureConnection::withTrashed()
            ->find(
                $connectionId,
            );

        if (! $connection) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'The configured Redis infrastructure connection no longer exists.',
            ]);
        }

        if ($connection->trashed()) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'The configured Redis infrastructure connection is archived.',
            ]);
        }

        if (
            $connection->type !==
            InfrastructureConnectionType::Redis
        ) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'The configured infrastructure connection is not Redis.',
            ]);
        }

        if (! $connection->is_enabled) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'The configured Redis infrastructure connection is disabled.',
            ]);
        }

        return $connection;
    }
}
