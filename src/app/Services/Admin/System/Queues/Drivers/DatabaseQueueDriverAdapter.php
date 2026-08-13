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
                'database_connections' => array_keys(
                    (array) config(
                        'database.connections',
                        [],
                    ),
                ),
            ],
        );
    }

    public function validateAndNormalize(
        array $configuration,
    ): array {
        $input = [
            'database_connection' => $configuration[
                'database_connection'
                ]
                ?? config(
                    'database.default',
                ),

            'retry_after' => $configuration[
                'retry_after'
                ]
                ?? config(
                    'simpledesk-queues.defaults.retry_after',
                    360,
                ),

            'after_commit' => $configuration[
                'after_commit'
                ]
                ?? config(
                    'simpledesk-queues.defaults.after_commit',
                    false,
                ),
        ];

        $validated =
            Validator::make(
                $input,
                [
                    'database_connection' => [
                        'required',
                        'string',
                        Rule::in(
                            $this
                                ->definition()
                                ->options[
                            'database_connections'
                            ],
                        ),
                    ],

                    'retry_after' => $this
                        ->safety
                        ->retryAfterRules(),

                    'after_commit' => [
                        'required',
                        'boolean',
                    ],
                ],
                $this
                    ->safety
                    ->retryAfterMessages(),
            )->validate();

        return [
            'database_connection' => $validated[
                'database_connection'
                ],

            'retry_after' => (int) $validated[
                'retry_after'
                ],

            'after_commit' => (bool) $validated[
                'after_commit'
                ],
        ];
    }

    public function runtimeConfiguration(
        QueueDriverConfiguration $configuration,
    ): QueueRuntimeConfigurationData {
        $values =
            $this->validateAndNormalize(
                $configuration->configuration ?? [],
            );

        return new QueueRuntimeConfigurationData(
            queueConnection: [
                'driver' => 'database',

                'connection' => $values[
                    'database_connection'
                    ],

                'table' => 'jobs',

                'queue' => 'default',

                'retry_after' => $values[
                    'retry_after'
                    ],

                'after_commit' => $values[
                    'after_commit'
                    ],
            ],
        );
    }

    public function test(QueueDriverConfiguration $configuration): QueueHealthResultData
    {
        $started = hrtime(true);
        try {
            $values = $this->validateAndNormalize($configuration->configuration ?? []);
            $connection = DB::connection($values['database_connection']);
            $connection->select('select 1');
            if (! Schema::connection($values['database_connection'])->hasTable('jobs')) {
                return new QueueHealthResultData(QueueHealthStatus::Unhealthy, $this->latency($started), 'The configured queue table is not available.', ['database_connection' => $values['database_connection'], 'table' => 'jobs']);
            }

return new QueueHealthResultData(QueueHealthStatus::Healthy, $this->latency($started), 'Database queue storage is usable.', ['database_connection' => $values['database_connection'], 'table' => 'jobs']);
        } catch (Throwable) {
            return new QueueHealthResultData(QueueHealthStatus::Unavailable, $this->latency($started), 'Database queue storage is unavailable.');
        }
    }

    private function latency(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }
}
