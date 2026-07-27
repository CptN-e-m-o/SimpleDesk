<?php

namespace App\Contracts\Admin\Mail;

use App\Data\Mail\MailConnectionTestResultData;
use App\Data\Mail\OutgoingEmailMessageData;
use App\Data\Mail\OutgoingSendResultData;
use App\Enums\Mail\MailboxDriver;
use App\Models\Admin\Mail\MailboxChannel;

interface OutgoingMailDriver
{
    public function driver(): MailboxDriver;

    public function test(
        MailboxChannel $channel
    ): MailConnectionTestResultData;

    public function send(
        MailboxChannel $channel,
        OutgoingEmailMessageData $message,
    ): OutgoingSendResultData;
}
