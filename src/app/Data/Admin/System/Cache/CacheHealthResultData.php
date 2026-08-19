<?php

namespace App\Data\Admin\System\Cache;

use App\Enums\Admin\System\CacheHealthStatus;

final readonly class CacheHealthResultData
{
    public function __construct(public CacheHealthStatus $status, public int $latencyMs, public string $message, public array $details = []) {}

    public function toArray(): array
    {
        return ['status' => $this->status->value, 'latency_ms' => $this->latencyMs, 'message' => $this->message, 'details' => $this->details];
    }
}
