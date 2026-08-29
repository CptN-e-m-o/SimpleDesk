<?php

namespace App\Services\Admin\System\Infrastructure;

use App\Data\Admin\System\Infrastructure\InfrastructureHealthResultData;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Enums\Admin\System\InfrastructureHealthTrigger;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use Throwable;

class InfrastructureConnectionHealthService
{
    public function __construct(
        private readonly InfrastructureConnectionRegistry $registry,
        private readonly InfrastructureSecretRedactor $redactor,
        private readonly SystemAuditLogger $audit,
    ) {}

    public function test(
        InfrastructureConnection $connection,
        InfrastructureHealthTrigger $trigger,
        ?User $actor = null,
    ): InfrastructureHealthResultData {
        try {
            $secrets = $connection->secrets();
        } catch (Throwable) {
            return $this->persist(
                $connection,
                new InfrastructureHealthResultData(
                    InfrastructureHealthStatus::Unavailable,
                    null,
                    'Stored infrastructure credentials could not be read.',
                ),
                [],
                $trigger,
                $actor,
            );
        }

        try {
            $result = $this->registry
                ->adapter($connection->type)
                ->test($connection);
        } catch (Throwable) {
            $result = new InfrastructureHealthResultData(
                InfrastructureHealthStatus::Unavailable,
                null,
                'The infrastructure provider could not be verified.',
            );
        }

        return $this->persist(
            $connection,
            $result,
            $secrets,
            $trigger,
            $actor,
        );
    }

    private function persist(
        InfrastructureConnection $connection,
        InfrastructureHealthResultData $result,
        array $secrets,
        InfrastructureHealthTrigger $trigger,
        ?User $actor,
    ): InfrastructureHealthResultData {
        $safe = new InfrastructureHealthResultData(
            $result->status,
            $result->latencyMs,
            $this->redactor->redact(
                $result->message,
                $secrets,
            ),
            $this->redactor->redact(
                $result->details,
                $secrets,
            ),
        );

        $connection->healthChecks()->create([
            'status' => $safe->status,
            'latency_ms' => $safe->latencyMs,
            'message' => $safe->message,
            'details' => $safe->details,
            'trigger' => $trigger,
            'checked_by' => $trigger === InfrastructureHealthTrigger::Manual
                ? $actor?->id
                : null,
        ]);

        $this->audit->log(
            'infrastructure_connections',
            'test',
            InfrastructureConnection::class,
            $connection->id,
            null,
            null,
            $safe->toArray(),
            $actor,
        );

        return $safe;
    }
}
