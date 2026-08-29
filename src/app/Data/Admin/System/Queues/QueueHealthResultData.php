<?php

namespace App\Data\Admin\System\Queues;

use App\Enums\Admin\System\QueueHealthStatus;

final readonly class QueueHealthResultData
{
    public function __construct(public QueueHealthStatus $status, public ?int $latencyMs, public string $message, public array $details = []) {}

    public function toArray(): array
    {
        return ['status' => $this->status->value, 'latency_ms' => $this->latencyMs, 'message' => $this->message, 'details' => $this->details];
    }
}
