<?php

namespace App\Data\Admin\System\Infrastructure;

use App\Enums\Admin\System\InfrastructureHealthStatus;

final readonly class InfrastructureHealthResultData
{
    public function __construct(
        public InfrastructureHealthStatus $status,
        public ?int $latencyMs,
        public ?string $message,
        public array $details = [],
    ) {}

    public function toArray(): array
    {
        return ['status' => $this->status->value, 'latency_ms' => $this->latencyMs, 'message' => $this->message, 'details' => $this->details];
    }
}
