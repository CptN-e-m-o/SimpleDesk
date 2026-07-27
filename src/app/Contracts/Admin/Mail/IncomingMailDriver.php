<?php

namespace App\Contracts\Admin\Mail;

use App\Data\Admin\Mail\IncomingCursorData;
use App\Data\Admin\Mail\IncomingFetchResultData;
use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Data\Admin\Mail\NormalizedInboundMessageData;
use App\Enums\Admin\Mail\IncomingAcknowledgeAction;
use App\Enums\Admin\Mail\MailboxDriver;
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
