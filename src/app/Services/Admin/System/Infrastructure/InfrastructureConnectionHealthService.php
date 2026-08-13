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
    public function __construct(private readonly InfrastructureConnectionRegistry $registry, private readonly InfrastructureSecretRedactor $redactor, private readonly SystemAuditLogger $audit) {}

    public function test(InfrastructureConnection $connection, InfrastructureHealthTrigger $trigger, ?User $actor = null): InfrastructureHealthResultData
    {
        $adapter = $this->registry->adapter($connection->type);
        try {
            $result = $adapter->test($connection);
        } catch (Throwable $e) {
            $result = new InfrastructureHealthResultData(InfrastructureHealthStatus::Unavailable, null, $e->getMessage());
        }
        $result = new InfrastructureHealthResultData($result->status, $result->latencyMs, $this->redactor->redact($result->message, $connection->secrets()), $this->redactor->redact($result->details, $connection->secrets()));
        $connection->healthChecks()->create(['status' => $result->status, 'latency_ms' => $result->latencyMs, 'message' => $result->message, 'details' => $result->details, 'trigger' => $trigger, 'checked_by' => $trigger === InfrastructureHealthTrigger::Manual ? $actor?->id : null]);
        $this->audit->log('infrastructure_connections', 'test', InfrastructureConnection::class, $connection->id, null, null, $result->toArray(), $actor);

        return $result;
    }
}
