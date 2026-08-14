<?php

namespace App\Services\Admin\System\Queues;

use App\Data\Admin\System\Queues\QueueHealthResultData;
use App\Enums\Admin\System\QueueHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Infrastructure\InfrastructureSecretRedactor;
use Throwable;

class QueueDriverHealthService
{
    public function __construct(
        private readonly QueueDriverRegistry $registry,
        private readonly InfrastructureSecretRedactor $redactor,
        private readonly SystemAuditLogger $audit,
    ) {}

    public function test(
        QueueDriverConfiguration $configuration,
        ?User $actor = null,
    ): QueueHealthResultData {
        $secrets =
            $this->secretValues(
                $configuration,
            );

        try {
            $result =
                $this
                    ->registry
                    ->adapter(
                        $configuration->driver,
                    )
                    ->test(
                        $configuration,
                    );
        } catch (Throwable) {
            $result =
                new QueueHealthResultData(
                    status: QueueHealthStatus::Unavailable,

                    latencyMs: null,

                    message: 'Queue configuration test failed safely.',
                );
        }

        $result =
            new QueueHealthResultData(
                status: $result->status,

                latencyMs: $result->latencyMs,

                message: (string) $this
                    ->redactor
                    ->redact(
                        $result->message,
                        $secrets,
                    ),

                details: (array) $this
                    ->redactor
                    ->redact(
                        $result->details,
                        $secrets,
                    ),
            );

        $configuration
            ->healthChecks()
            ->create([
                'status' => $result->status,

                'latency_ms' => $result->latencyMs,

                'message' => $result->message,

                'details' => $result->details,

                'tested_by' => $actor?->id,
            ]);

        $this->audit->log(
            area: 'queue_driver_configurations',

            action: 'test',

            subjectType: QueueDriverConfiguration::class,

            subjectId: $configuration->id,

            before: null,

            after: null,

            metadata: [
                'status' => $result
                    ->status
                    ->value,

                'latency_ms' => $result->latencyMs,
            ],

            actor: $actor,
        );

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function secretValues(
        QueueDriverConfiguration $configuration,
    ): array {
        $connectionId =
            data_get(
                $configuration->configuration,
                'infrastructure_connection_id',
            );

        if (
            ! is_numeric(
                $connectionId,
            )
            || (int) $connectionId <= 0
        ) {
            return [];
        }

        $connection =
            InfrastructureConnection::withTrashed()
                ->find(
                    (int) $connectionId,
                );

        if (! $connection) {
            return [];
        }

        return $connection->secrets();
    }
}
