<?php

namespace App\Services\Admin\Mail;

use App\Data\Admin\Mail\NormalizedInboundMessageData;
use App\Enums\Admin\Mail\IncomingAcknowledgeAction;
use App\Models\Admin\Mail\MailboxChannel;

class IncomingMailAcknowledger
{
    public function __construct(
        private readonly MailDriverRegistry $drivers,
    ) {
    }

    public function acknowledge(
        MailboxChannel $channel,
        NormalizedInboundMessageData $message,
        IncomingAcknowledgeAction $action,
    ): void {
        $driver = $this->drivers->incoming(
            $channel->driver
        );

        $driver->acknowledge(
            channel: $channel,
            message: $message,
            action: $action,
        );
    }

    /**
     * @param array<int, NormalizedInboundMessageData> $messages
     */
    public function acknowledgeMany(
        MailboxChannel $channel,
        array $messages,
        IncomingAcknowledgeAction $action,
    ): int {
        $driver = $this->drivers->incoming(
            $channel->driver
        );

        return $driver->acknowledgeMany(
            channel: $channel,
            messages: $messages,
            action: $action,
        );
    }
}
