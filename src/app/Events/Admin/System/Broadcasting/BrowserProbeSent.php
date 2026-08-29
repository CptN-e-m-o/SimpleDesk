<?php

namespace App\Events\Admin\System\Broadcasting;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

final class BrowserProbeSent implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly string $probeId,
        public readonly string $sentAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("users.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'system.broadcasting.browser-probe';
    }

    public function broadcastWith(): array
    {
        return [
            'probe_id' => $this->probeId,
            'sent_at' => $this->sentAt,
        ];
    }
}
