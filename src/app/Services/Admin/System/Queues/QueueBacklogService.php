<?php

namespace App\Services\Admin\System\Queues;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Throwable;

class QueueBacklogService
{
    public function __construct(
        private readonly QueueFactory $queues,
        private readonly QueueWorkloadRegistry $workloads,
    ) {}

    public function inspect(): array
    {
        $pairs = [];

        $defaultConnection = trim(
            (string) config(
                'queue.default',
            ),
        );

        foreach (
            $this->workloads->definitions() as $workload
        ) {
            $connection =
                $workload->connectionName
                ?? $defaultConnection;

            $key =
                $connection
                ."\0"
                .$workload->queueName;

            $pairs[$key] ??= [
                'connection' => $connection,
                'queue' => $workload->queueName,
                'pending' => null,
                'inspectable' => false,
                'error' => null,
                'workloads' => [],
            ];

            $pairs[$key]['workloads'][] = [
                'key' => $workload->key,
                'label' => $workload->label,
            ];
        }

        $inspectedPending = 0;
        $isComplete = true;

        foreach ($pairs as &$pair) {
            try {
                $pending = (int) $this
                    ->queues
                    ->connection(
                        $pair['connection'],
                    )
                    ->size(
                        $pair['queue'],
                    );

                $pair['pending'] = $pending;
                $pair['inspectable'] = true;

                $inspectedPending += $pending;
            } catch (Throwable) {
                $pair['pending'] = null;
                $pair['inspectable'] = false;
                $pair['error'] =
                    'Queue backlog is not inspectable for this connection.';

                $isComplete = false;
            }
        }

        unset($pair);

        return [
            'queues' => array_values(
                $pairs,
            ),

            'total_pending' => $isComplete
                    ? $inspectedPending
                    : null,

            'inspected_pending' => $inspectedPending,

            'is_complete' => $isComplete,

            'has_errors' => ! $isComplete,

            'inspected_at' => now()->toIso8601String(),
        ];
    }
}
