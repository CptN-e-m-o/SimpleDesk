<?php

namespace App\Data\Admin\System\Storage;

use App\Enums\Admin\System\StorageHealthStatus;

final readonly class StorageHealthResultData
{
    public function __construct(public StorageHealthStatus $status, public ?int $latencyMs = null, public ?string $message = null, public array $details = []) {}

    public function toArray(): array
    {
        return ['status' => $this->status->value, 'latency_ms' => $this->latencyMs, 'message' => $this->message, 'details' => $this->details];
    }
}
