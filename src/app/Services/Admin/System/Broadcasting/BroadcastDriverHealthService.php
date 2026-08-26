<?php

namespace App\Services\Admin\System\Broadcasting;

use App\Data\Admin\System\Broadcasting\BroadcastHealthResultData;
use App\Enums\Admin\System\BroadcastHealthStatus;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Infrastructure\InfrastructureSecretRedactor;
use Illuminate\Validation\ValidationException;
use Throwable;

class BroadcastDriverHealthService
{
    public function __construct(
        private readonly BroadcastDriverRegistry $registry,
        private readonly InfrastructureSecretRedactor $redactor,
        private readonly SystemAuditLogger $audit,
    ) {}

    public function test(BroadcastDriverConfiguration $configuration, ?User $actor = null): BroadcastHealthResultData
    {
        return $this->run($configuration, $actor, 'test');
    }

    public function preflight(BroadcastDriverConfiguration $configuration, ?User $actor = null): BroadcastHealthResultData
    {
        return $this->run($configuration, $actor, 'activation_preflight');
    }

    private function run(
        BroadcastDriverConfiguration $configuration,
        ?User $actor,
        string $action,
    ): BroadcastHealthResultData {
        $secrets = [];

        try {
            if ($configuration->infrastructure_connection_id) {
                $connection = InfrastructureConnection::withTrashed()->find(
                    $configuration->infrastructure_connection_id,
                );

                $secrets = $connection?->secrets() ?? [];
            }

            $result = $this->registry
                ->adapter($configuration->driver)
                ->test($configuration);
        } catch (ValidationException $exception) {
            $result = new BroadcastHealthResultData(
                BroadcastHealthStatus::Unavailable,
                0,
                $this->validationMessage($exception),
            );
        } catch (Throwable) {
            $result = new BroadcastHealthResultData(
                BroadcastHealthStatus::Unavailable,
                0,
                'Broadcast target could not be verified.',
            );
        }

        $safe = new BroadcastHealthResultData(
            $result->status,
            $result->latencyMs,
            (string) $this->redactor->redact($result->message, $secrets),
            (array) $this->redactor->redact($result->details, $secrets),
        );

        $configuration->healthChecks()->create([
            'status' => $safe->status,
            'latency_ms' => $safe->latencyMs,
            'message' => $safe->message,
            'details' => $safe->details,
            'tested_by' => $actor?->id,
        ]);

        $this->audit->log(
            area: 'broadcast_driver_configurations',
            action: $action,
            subjectType: BroadcastDriverConfiguration::class,
            subjectId: $configuration->id,
            before: null,
            after: null,
            metadata: [
                'status' => $safe->status->value,
                'latency_ms' => $safe->latencyMs,
            ],
            actor: $actor,
        );

        return $safe;
    }

    private function validationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            foreach ($messages as $message) {
                if (is_string($message) && trim($message) !== '') {
                    return trim($message);
                }
            }
        }

        return 'Broadcast target could not be verified.';
    }
}
