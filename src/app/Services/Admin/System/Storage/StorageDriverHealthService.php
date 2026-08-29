<?php

namespace App\Services\Admin\System\Storage;

use App\Data\Admin\System\Storage\StorageHealthResultData;
use App\Enums\Admin\System\StorageHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\StorageDriverConfiguration;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Infrastructure\InfrastructureSecretRedactor;
use Illuminate\Validation\ValidationException;
use Throwable;

class StorageDriverHealthService
{
    public function __construct(
        private readonly StorageDriverRegistry $registry,
        private readonly InfrastructureSecretRedactor $redactor,
        private readonly SystemAuditLogger $audit,
    ) {}

    public function test(
        StorageDriverConfiguration $configuration,
        ?User $actor = null,
    ): StorageHealthResultData {
        return $this->run(
            $configuration,
            $actor,
            'health_test',
        );
    }

    public function preflight(
        StorageDriverConfiguration $configuration,
        ?User $actor = null,
    ): StorageHealthResultData {
        return $this->run(
            $configuration,
            $actor,
            'activation_preflight',
        );
    }

    private function run(
        StorageDriverConfiguration $configuration,
        ?User $actor,
        string $action,
    ): StorageHealthResultData {
        try {
            $secrets = $this->loadSecrets($configuration);
        } catch (Throwable) {
            return $this->persist(
                $configuration,
                new StorageHealthResultData(
                    StorageHealthStatus::Unavailable,
                    0,
                    'Stored Storage provider credentials could not be read.',
                ),
                [],
                $actor,
                $action,
            );
        }

        try {
            $result = $this->registry
                ->adapter($configuration->driver)
                ->test($configuration);
        } catch (ValidationException $exception) {
            $result = new StorageHealthResultData(
                StorageHealthStatus::Unavailable,
                0,
                collect($exception->errors())
                    ->flatten()
                    ->first()
                ?? 'Storage target could not be verified.',
            );
        } catch (Throwable) {
            $result = new StorageHealthResultData(
                StorageHealthStatus::Unavailable,
                0,
                'Storage target could not be verified.',
            );
        }

        return $this->persist(
            $configuration,
            $result,
            $secrets,
            $actor,
            $action,
        );
    }

    private function loadSecrets(
        StorageDriverConfiguration $configuration,
    ): array {
        if (! $configuration->infrastructure_connection_id) {
            return [];
        }

        $connection = InfrastructureConnection::withTrashed()
            ->find($configuration->infrastructure_connection_id);

        return $connection?->secrets() ?? [];
    }

    private function persist(
        StorageDriverConfiguration $configuration,
        StorageHealthResultData $result,
        array $secrets,
        ?User $actor,
        string $action,
    ): StorageHealthResultData {
        $safe = new StorageHealthResultData(
            $result->status,
            $result->latencyMs,
            (string) $this->redactor->redact(
                $result->message,
                $secrets,
            ),
            (array) $this->redactor->redact(
                $result->details,
                $secrets,
            ),
        );

        $configuration->healthChecks()->create([
            'status' => $safe->status,
            'latency_ms' => $safe->latencyMs,
            'message' => $safe->message,
            'details' => $safe->details,
            'tested_by' => $actor?->id,
        ]);

        $this->audit->log(
            'storage_driver_configurations',
            $action,
            StorageDriverConfiguration::class,
            $configuration->id,
            null,
            null,
            [
                'status' => $safe->status->value,
                'latency_ms' => $safe->latencyMs,
            ],
            $actor,
        );

        return $safe;
    }
}
