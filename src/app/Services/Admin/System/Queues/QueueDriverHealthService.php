<?php

namespace App\Services\Admin\System\Queues;

use App\Data\Admin\System\Queues\QueueHealthResultData;
use App\Enums\Admin\System\QueueHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Infrastructure\InfrastructureSecretRedactor;
use Illuminate\Validation\ValidationException;
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
        return $this->run(
            configuration: $configuration,
            actor: $actor,
            auditAction: 'test',
        );
    }

    public function preflight(
        QueueDriverConfiguration $configuration,
        ?User $actor = null,
    ): QueueHealthResultData {
        return $this->run(
            configuration: $configuration,
            actor: $actor,
            auditAction: 'activation_preflight',
        );
    }

    private function run(
        QueueDriverConfiguration $configuration,
        ?User $actor,
        string $auditAction,
    ): QueueHealthResultData {
        $secrets = $this->secretValues($configuration);

        try {
            $result = $this->registry
                ->adapter($configuration->driver)
                ->test($configuration);
        } catch (ValidationException $exception) {
            $result = new QueueHealthResultData(
                status: QueueHealthStatus::Unavailable,
                latencyMs: null,
                message: $this->validationMessage($exception),
            );
        } catch (Throwable) {
            $result = new QueueHealthResultData(
                status: QueueHealthStatus::Unavailable,
                latencyMs: null,
                message: 'Queue connectivity could not be verified.',
            );
        }

        $result = new QueueHealthResultData(
            status: $result->status,
            latencyMs: $result->latencyMs,
            message: (string) $this->redactor->redact(
                $result->message,
                $secrets,
            ),
            details: (array) $this->redactor->redact(
                $result->details,
                $secrets,
            ),
        );

        $configuration->healthChecks()->create([
            'status' => $result->status,
            'latency_ms' => $result->latencyMs,
            'message' => $result->message,
            'details' => $result->details,
            'tested_by' => $actor?->id,
        ]);

        $this->audit->log(
            area: 'queue_driver_configurations',
            action: $auditAction,
            subjectType: QueueDriverConfiguration::class,
            subjectId: $configuration->id,
            before: null,
            after: null,
            metadata: [
                'status' => $result->status->value,
                'latency_ms' => $result->latencyMs,
            ],
            actor: $actor,
        );

        return $result;
    }

    private function validationMessage(
        ValidationException $exception,
    ): string {
        foreach ($exception->errors() as $messages) {
            foreach ($messages as $message) {
                if (is_string($message) && trim($message) !== '') {
                    return trim($message);
                }
            }
        }

        return 'Queue configuration validation failed.';
    }

    private function secretValues(
        QueueDriverConfiguration $configuration,
    ): array {
        $connectionId = $configuration->infrastructure_connection_id;

        if (! $connectionId) {
            return [];
        }

        $connection = InfrastructureConnection::withTrashed()->find(
            $connectionId,
        );

        if (! $connection) {
            return [];
        }

        return $connection->secrets();
    }
}
