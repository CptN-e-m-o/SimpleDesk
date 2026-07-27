<?php

namespace App\Contracts\Admin\Mail;

use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Data\Admin\Mail\OutgoingSendResultData;
use App\Enums\Admin\Mail\MailboxDriver;
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
