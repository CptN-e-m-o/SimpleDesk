<?php

namespace App\Services\Admin\System\Queues;

use App\Data\Admin\System\Queues\QueueHealthResultData;
use App\Enums\Admin\System\QueueHealthStatus;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Infrastructure\InfrastructureSecretRedactor;
use Throwable;

class QueueDriverHealthService
{
    public function __construct(private readonly QueueDriverRegistry $registry, private readonly InfrastructureSecretRedactor $redactor, private readonly SystemAuditLogger $audit) {}

    public function test(QueueDriverConfiguration $configuration, ?User $actor = null): QueueHealthResultData
    {
        try {
            $result = $this->registry->adapter($configuration->driver)->test($configuration);
        } catch (Throwable) {
            $result = new QueueHealthResultData(QueueHealthStatus::Unavailable, null, 'Queue configuration test failed safely.');
        }$result = new QueueHealthResultData($result->status, $result->latencyMs, (string) $this->redactor->redact($result->message, []), (array) $this->redactor->redact($result->details, []));
        $configuration->healthChecks()->create(['status' => $result->status, 'latency_ms' => $result->latencyMs, 'message' => $result->message, 'details' => $result->details, 'tested_by' => $actor?->id]);
        $this->audit->log('queue_driver_configurations', 'test', QueueDriverConfiguration::class, $configuration->id, null, null, ['status' => $result->status->value, 'latency_ms' => $result->latencyMs], $actor);

        return $result;
    }
}
