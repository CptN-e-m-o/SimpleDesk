<?php

namespace Tests\Fakes\Admin\Mail;

use App\Contracts\Admin\Mail\OutgoingMailDriver;
use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Data\Admin\Mail\OutgoingSendResultData;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Models\Admin\Mail\MailboxChannel;
use LogicException;
use Throwable;

class FakeOutgoingMailDriver implements OutgoingMailDriver
{
    public function __construct(
        public MailConnectionTestResultData $testResult,
        public ?Throwable $testException = null,
    ) {}

    public function driver(): MailboxDriver
    {
        return MailboxDriver::Smtp;
    }

    public function test(
        MailboxChannel $channel
    ): MailConnectionTestResultData {
        if ($this->testException !== null) {
            throw $this->testException;
        }

        return $this->testResult;
    }

    public function send(
        MailboxChannel $channel,
        OutgoingEmailMessageData $message,
    ): OutgoingSendResultData {
        throw new LogicException(
            'Fake outgoing driver does not implement send().'
        );
    }
}
