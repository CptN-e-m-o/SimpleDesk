<?php

namespace App\Services\Admin\System\Queues;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Throwable;

class QueueBacklogService
{
    public function __construct(private readonly QueueFactory $queues, private readonly QueueWorkloadRegistry $workloads) {}

    public function inspect(): array
    {
        $pairs = [];
        $default = (string) config('queue.default');
        foreach ($this->workloads->definitions() as $workload) {
            $connection = $workload->connectionName ?? $default;
            $key = $connection."\0".$workload->queueName;
            $pairs[$key] ??= ['connection' => $connection, 'queue' => $workload->queueName, 'pending' => null, 'inspectable' => false, 'error' => null, 'workloads' => []];
            $pairs[$key]['workloads'][] = ['key' => $workload->key, 'label' => $workload->label];
        } $total = 0;
        $errors = false;
        foreach ($pairs as &$pair) {
            try {
                $pair['pending'] = $this->queues->connection($pair['connection'])->size($pair['queue']);
                $pair['inspectable'] = true;
                $total += $pair['pending'];
            } catch (Throwable) {
                $pair['error'] = 'Queue backlog is not inspectable for this connection.';
                $errors = true;
            }
        }unset($pair);

        return ['queues' => array_values($pairs), 'total_pending' => $total, 'has_errors' => $errors, 'inspected_at' => now()->toIso8601String()];
    }
}
