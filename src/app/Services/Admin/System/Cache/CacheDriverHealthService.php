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
    public function __construct(
        private readonly CacheDriverRegistry $registry,
        private readonly InfrastructureSecretRedactor $redactor,
        private readonly SystemAuditLogger $audit,
    ) {}

    public function test(
        CacheDriverConfiguration $configuration,
        ?User $actor = null,
    ): CacheHealthResultData {
        return $this->run(
            configuration: $configuration,
            actor: $actor,
            action: 'test',
        );
    }

    public function preflight(
        CacheDriverConfiguration $configuration,
        ?User $actor = null,
    ): CacheHealthResultData {
        return $this->run(
            configuration: $configuration,
            actor: $actor,
            action: 'activation_preflight',
        );
    }

    private function run(
        CacheDriverConfiguration $configuration,
        ?User $actor,
        string $action,
    ): CacheHealthResultData {
        $secrets = $this->secretValues($configuration);

        try {
            $result = $this->registry
                ->adapter($configuration->driver)
                ->test($configuration);
        } catch (ValidationException $exception) {
            $result = new CacheHealthResultData(
                status: CacheHealthStatus::Unavailable,
                latencyMs: 0,
                message: $this->validationMessage($exception),
            );
        } catch (Throwable) {
            $result = new CacheHealthResultData(
                status: CacheHealthStatus::Unavailable,
                latencyMs: 0,
                message: 'Cache target could not be verified.',
            );
        }

        $result = new CacheHealthResultData(
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
            area: 'cache_driver_configurations',
            action: $action,
            subjectType: CacheDriverConfiguration::class,
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

        return 'Cache configuration validation failed.';
    }

    private function secretValues(
        CacheDriverConfiguration $configuration,
    ): array {
        if (! $configuration->infrastructure_connection_id) {
            return [];
        }

        try {
            return InfrastructureConnection::withTrashed()
                ->find($configuration->infrastructure_connection_id)
                ?->secrets() ?? [];
        } catch (Throwable) {
            return [];
        }
    }
}
