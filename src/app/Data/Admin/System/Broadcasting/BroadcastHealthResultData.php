<?php

namespace App\Data\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastHealthStatus;

readonly class BroadcastHealthResultData
{
    public function __construct(public BroadcastHealthStatus $status, public int $latencyMs, public string $message, public array $details = []) {}

    public function toArray(): array
    {
        return ['status' => $this->status->value, 'latency_ms' => $this->latencyMs, 'message' => $this->message, 'details' => $this->details];
    }
}
