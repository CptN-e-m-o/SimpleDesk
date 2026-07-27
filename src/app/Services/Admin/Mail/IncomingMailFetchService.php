<?php

namespace App\Services\Mail;

use App\Data\Admin\Mail\IncomingCursorData;
use App\Data\Admin\Mail\IncomingFetchResultData;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Models\Admin\Mail\MailboxChannel;
use InvalidArgumentException;

class IncomingMailFetchService
{
    public function __construct(
        private readonly MailDriverRegistry $drivers,
    ) {
    }

    public function fetch(
        MailboxChannel $channel,
        ?IncomingCursorData $cursor = null,
        int $limit = 100,
    ): IncomingFetchResultData {
        if (
            $channel->direction
            !== MailboxChannelDirection::Incoming
        ) {
            throw new InvalidArgumentException(
                "Mailbox channel [{$channel->id}] "
                . 'is not an incoming channel.'
            );
        }

        $limit = max(1, min($limit, 500));

        $driver = $this->drivers->incoming(
            $channel->driver
        );

        $result = $driver->fetch(
            channel: $channel,
            cursor: $cursor,
            limit: $limit,
        );

        return $result->withSource(
            mailboxChannelId: $channel->id,
            driver: $channel->driver,
        );
    }
}
