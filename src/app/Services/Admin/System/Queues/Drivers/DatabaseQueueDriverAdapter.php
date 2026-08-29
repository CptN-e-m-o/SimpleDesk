<?php

namespace App\Services\Admin\System\Queues\Drivers;

use App\Contracts\Admin\System\Queues\QueueDriverAdapter;
use App\Data\Admin\System\Queues\QueueDriverDefinitionData;
use App\Data\Admin\System\Queues\QueueHealthResultData;
use App\Data\Admin\System\Queues\QueueRuntimeConfigurationData;
use App\Enums\Admin\System\QueueDriverType;
use App\Enums\Admin\System\QueueHealthStatus;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Services\Admin\System\Queues\QueueSafetyPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class DatabaseQueueDriverAdapter implements QueueDriverAdapter
{
    public function __construct(
        private readonly QueueSafetyPolicy $safety,
    ) {}

    public function type(): QueueDriverType
    {
        return QueueDriverType::Database;
    }

    public function definition(): QueueDriverDefinitionData
    {
        return new QueueDriverDefinitionData(
            type: $this->type(),
            label: 'Database',
            description: 'Store queued jobs in an application database.',
            requiresInfrastructure: false,
            infrastructureType: null,
            recommendedForProduction: true,
            options: [
                'database_connections' => $this->allowedConnections(),
            ],
        );
    }

    public function validateAndNormalize(
        array $configuration,
    ): array {
        $input = [
            'database_connection' => $configuration['database_connection']
                ?? config('database.default'),

            'retry_after' => $configuration['retry_after']
                ?? config(
                    'simpledesk-queues.defaults.retry_after',
                    360,
                ),

            'after_commit' => $configuration['after_commit']
                ?? config(
                    'simpledesk-queues.defaults.after_commit',
                    false,
                ),
        ];

        $validated = Validator::make(
            $input,
            [
                'database_connection' => [
                    'required',
                    'string',
                    Rule::in(
                        $this->allowedConnections(),
                    ),
                ],

                'retry_after' => $this->safety->retryAfterRules(),

                'after_commit' => [
                    'required',
                    'boolean',
                ],
            ],
            $this->safety->retryAfterMessages(),
        )->validate();

        return [
            'database_connection' => $validated['database_connection'],

            'retry_after' => (int) $validated['retry_after'],

            'after_commit' => (bool) $validated['after_commit'],
        ];
    }

    public function runtimeConfiguration(
        QueueDriverConfiguration $configuration,
    ): QueueRuntimeConfigurationData {
        $values = $this->validateAndNormalize(
            $configuration->configuration ?? [],
        );

        return new QueueRuntimeConfigurationData(
            queueConnection: [
                'driver' => 'database',
                'connection' => $values['database_connection'],
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => $values['retry_after'],
                'after_commit' => $values['after_commit'],
            ],
        );
    }

    public function test(
        QueueDriverConfiguration $configuration,
    ): QueueHealthResultData {
        $started = hrtime(true);

        try {
            $values = $this->validateAndNormalize(
                $configuration->configuration ?? [],
            );

            $connectionName =
                $values['database_connection'];

            $connection = DB::connection(
                $connectionName,
            );

            $connection->select('select 1');

            if (
                ! Schema::connection(
                    $connectionName,
                )->hasTable('jobs')
            ) {
                return new QueueHealthResultData(
                    status: QueueHealthStatus::Unhealthy,
                    latencyMs: $this->latency($started),
                    message: 'The configured queue table is not available.',
                    details: [
                        'database_connection' => $connectionName,
                        'table' => 'jobs',
                    ],
                );
            }

            return new QueueHealthResultData(
                status: QueueHealthStatus::Healthy,
                latencyMs: $this->latency($started),
                message: 'Database queue storage is usable.',
                details: [
                    'database_connection' => $connectionName,
                    'table' => 'jobs',
                ],
            );
        } catch (Throwable) {
            return new QueueHealthResultData(
                status: QueueHealthStatus::Unavailable,
                latencyMs: $this->latency($started),
                message: 'Database queue storage is unavailable.',
            );
        }
    }

    private function allowedConnections(): array
    {
        $available = array_keys(
            (array) config(
                'database.connections',
                [],
            ),
        );

        $configured = array_values(
            array_unique(
                array_filter(
                    array_map(
                        fn (mixed $value): string => trim((string) $value),
                        (array) config(
                            'simpledesk-queues.database.allowed_connections',
                            [],
                        ),
                    ),
                    fn (string $value): bool => $value !== '',
                ),
            ),
        );

        if ($configured !== []) {
            return array_values(
                array_intersect(
                    $configured,
                    $available,
                ),
            );
        }

        $default = trim(
            (string) config(
                'database.default',
                '',
            ),
        );

        if (
            $default === ''
            || ! in_array(
                $default,
                $available,
                true,
            )
        ) {
            return [];
        }

        return [$default];
    }

    private function latency(
        int $started,
    ): int {
        return (int) round(
            (hrtime(true) - $started)
            / 1_000_000,
        );
    }
}
