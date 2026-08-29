<?php

namespace App\Services\Admin\System\Search;

use App\Data\Admin\System\Search\SearchHealthResultData;
use App\Enums\Admin\System\SearchHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\SearchDriverConfiguration;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Infrastructure\InfrastructureSecretRedactor;
use Illuminate\Validation\ValidationException;
use Throwable;

class SearchDriverHealthService
{
    public function __construct(private readonly SearchDriverRegistry $registry, private readonly InfrastructureSecretRedactor $redactor, private readonly SystemAuditLogger $audit) {}

    public function test(SearchDriverConfiguration $configuration, ?User $actor = null): SearchHealthResultData
    {
        return $this->run($configuration, $actor, 'health_test');
    }

    public function preflight(SearchDriverConfiguration $configuration, ?User $actor = null): SearchHealthResultData
    {
        return $this->run($configuration, $actor, 'activation_preflight');
    }

    private function run(SearchDriverConfiguration $configuration, ?User $actor, string $action): SearchHealthResultData
    {
        $secrets = [];
        try {
            if ($configuration->infrastructure_connection_id) {
                $secrets = InfrastructureConnection::withTrashed()->find($configuration->infrastructure_connection_id)?->secrets() ?? [];
            }
            $result = $this->registry->adapter($configuration->driver)->test($configuration);
        } catch (ValidationException $exception) {
            $result = new SearchHealthResultData(SearchHealthStatus::Unavailable, 0, $this->validationMessage($exception));
        } catch (Throwable) {
            $result = new SearchHealthResultData(SearchHealthStatus::Unavailable, 0, 'Search target could not be verified.');
        }
        $safe = new SearchHealthResultData($result->status, $result->latencyMs, (string) $this->redactor->redact($result->message, $secrets), (array) $this->redactor->redact($result->details, $secrets));
        $configuration->healthChecks()->create(['status' => $safe->status, 'latency_ms' => $safe->latencyMs, 'message' => $safe->message, 'details' => $safe->details, 'tested_by' => $actor?->id]);
        $this->audit->log('search_driver_configurations', $action, SearchDriverConfiguration::class, $configuration->id, null, null, ['status' => $safe->status->value, 'latency_ms' => $safe->latencyMs], $actor);

        return $safe;
    }

    private function validationMessage(ValidationException $exception): string
    {
        return collect($exception->errors())->flatten()->first() ?? 'Search target could not be verified.';
    }
}
