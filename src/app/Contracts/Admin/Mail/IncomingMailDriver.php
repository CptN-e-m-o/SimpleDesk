<?php

namespace App\Contracts\Admin\Mail;

use App\Data\Mail\IncomingCursorData;
use App\Data\Mail\IncomingFetchResultData;
use App\Data\Mail\MailConnectionTestResultData;
use App\Data\Mail\NormalizedInboundMessageData;
use App\Enums\Mail\IncomingAcknowledgeAction;
use App\Enums\Mail\MailboxDriver;
use App\Models\Admin\Mail\MailboxChannel;

interface IncomingMailDriver
{
    public function driver(): MailboxDriver;

    public function test(
        MailboxChannel $channel
    ): MailConnectionTestResultData;

    public function fetch(
        MailboxChannel $channel,
        ?IncomingCursorData $cursor = null,
        int $limit = 100,
    ): IncomingFetchResultData;

    public function acknowledge(
        MailboxChannel $channel,
        NormalizedInboundMessageData $message,
        IncomingAcknowledgeAction $action,
    ): void;
}
