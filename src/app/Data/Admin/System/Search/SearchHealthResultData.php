<?php

namespace App\Data\Admin\System\Search;

use App\Enums\Admin\System\SearchHealthStatus;

final readonly class SearchHealthResultData
{
    public function __construct(public SearchHealthStatus $status, public ?int $latencyMs, public ?string $message, public array $details = []) {}

    public function toArray(): array
    {
        return ['status' => $this->status->value, 'latency_ms' => $this->latencyMs, 'message' => $this->message, 'details' => $this->details];
    }
}
