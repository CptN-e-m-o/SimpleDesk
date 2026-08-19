<?php

namespace App\Services\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Infrastructure\InfrastructureSecretRedactor;
use Illuminate\Validation\ValidationException;
use Throwable;

class CacheDriverHealthService
{
    public function __construct(private readonly CacheDriverRegistry $registry, private readonly InfrastructureSecretRedactor $redactor, private readonly SystemAuditLogger $audit) {}

    public function test(CacheDriverConfiguration $configuration, ?User $actor = null): CacheHealthResultData
    {
        return $this->run($configuration, $actor, 'test');
    }

    public function preflight(CacheDriverConfiguration $configuration, ?User $actor = null): CacheHealthResultData
    {
        return $this->run($configuration, $actor, 'activation_preflight');
    }

    private function run(CacheDriverConfiguration $configuration, ?User $actor, string $action): CacheHealthResultData
    {
        try {
            $result = $this->registry->adapter($configuration->driver)->test($configuration);
        } catch (ValidationException $e) {
            $result = new CacheHealthResultData(CacheHealthStatus::Unavailable, 0, (string) collect($e->errors())->flatten()->first());
        } catch (Throwable) {
            $result = new CacheHealthResultData(CacheHealthStatus::Unavailable, 0, 'Cache target could not be verified.');
        }
        $secrets = $configuration->infrastructure_connection_id
            ? InfrastructureConnection::withTrashed()->find($configuration->infrastructure_connection_id)?->secrets() ?? []
            : [];
        $result = new CacheHealthResultData($result->status, $result->latencyMs, (string) $this->redactor->redact($result->message, $secrets), (array) $this->redactor->redact($result->details, $secrets));
        $configuration->healthChecks()->create(['status' => $result->status, 'latency_ms' => $result->latencyMs, 'message' => $result->message, 'details' => $result->details, 'tested_by' => $actor?->id]);
        $this->audit->log('cache_driver_configurations', $action, CacheDriverConfiguration::class, $configuration->id, null, null, ['status' => $result->status->value, 'latency_ms' => $result->latencyMs], $actor);

        return $result;
    }
}
