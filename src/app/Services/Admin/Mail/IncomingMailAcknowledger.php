<?php

namespace App\Services\Mail;

use App\Data\Mail\NormalizedInboundMessageData;
use App\Enums\Mail\IncomingAcknowledgeAction;
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
}
