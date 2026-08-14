<?php

namespace App\Services\Admin\System\Queues;

class QueuePinnedWorkloadService
{
    public function __construct(
        private readonly QueueWorkloadRegistry $workloads,
    ) {}

    public function enabled(): array
    {
        $result = [];

        foreach ($this->workloads->definitions() as $workload) {
            if (! $workload->enabled || $workload->connectionName === null) {
                continue;
            }

            $result[] = [
                'key' => $workload->key,
                'label' => $workload->label,
                'connection' => $workload->connectionName,
                'queue' => $workload->queueName,
            ];
        }

        return $result;
    }
}
