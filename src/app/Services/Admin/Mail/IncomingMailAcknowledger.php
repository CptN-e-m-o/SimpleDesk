<?php

namespace App\Services\Mail;

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
}
