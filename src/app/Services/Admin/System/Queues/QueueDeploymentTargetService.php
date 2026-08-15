<?php

namespace App\Services\Admin\System\Queues;

use App\Data\Admin\System\Queues\QueueHealthResultData;
use App\Enums\Admin\System\QueueHealthStatus;
use Illuminate\Queue\QueueManager;
use Illuminate\Validation\ValidationException;
use Throwable;

class QueueDeploymentTargetService
{
    public function __construct(
        private readonly QueueManager $queues,
    ) {}

    public function resolve(): array
    {
        $connectionName = trim(
            (string) config('simpledesk-queues.deployment.connection', ''),
        );

        if ($connectionName === '') {
            throw ValidationException::withMessages([
                'activation' => 'The deployment Queue connection is not configured.',
            ]);
        }

        $managedConnectionName = trim(
            (string) config(
                'simpledesk-queues.runtime.connection_name',
                'simpledesk-managed',
            ),
        );

        if (
            $managedConnectionName !== ''
            && $connectionName === $managedConnectionName
        ) {
            throw ValidationException::withMessages([
                'activation' => 'The deployment Queue connection cannot use the managed runtime connection name.',
            ]);
        }

        $configuration = config(
            "queue.connections.{$connectionName}",
        );

        if (! is_array($configuration)) {
            throw ValidationException::withMessages([
                'activation' => "The deployment Queue connection [{$connectionName}] no longer exists.",
            ]);
        }

        $driver = trim(
            (string) ($configuration['driver'] ?? ''),
        );

        if ($driver === '') {
            throw ValidationException::withMessages([
                'activation' => "The deployment Queue connection [{$connectionName}] does not define a driver.",
            ]);
        }

        $queueName = trim(
            (string) ($configuration['queue'] ?? ''),
        );

        return [
            'connection' => $connectionName,
            'driver' => $driver,
            'queue' => $queueName !== '' ? $queueName : null,
        ];
    }

    public function test(
        ?array $target = null,
    ): QueueHealthResultData {
        $target ??= $this->resolve();

        $started = hrtime(true);

        try {
            $connection = $this->queues->connection(
                $target['connection'],
            );

            $pending = $connection->size(
                $target['queue'],
            );

            return new QueueHealthResultData(
                status: QueueHealthStatus::Healthy,
                latencyMs: $this->latency($started),
                message: 'Deployment Queue backend verified successfully.',
                details: [
                    'connection' => $target['connection'],
                    'driver' => $target['driver'],
                    'queue' => $target['queue'],
                    'pending_snapshot' => $pending,
                ],
            );
        } catch (Throwable) {
            return new QueueHealthResultData(
                status: QueueHealthStatus::Unavailable,
                latencyMs: $this->latency($started),
                message: 'Deployment Queue backend could not be verified.',
                details: [
                    'connection' => $target['connection'],
                    'driver' => $target['driver'],
                    'queue' => $target['queue'],
                ],
            );
        }
    }

    private function latency(int $started): int
    {
        return (int) round(
            (hrtime(true) - $started) / 1_000_000,
        );
    }
}
