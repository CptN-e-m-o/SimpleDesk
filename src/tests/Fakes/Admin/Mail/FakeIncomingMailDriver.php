<?php

namespace Tests\Fakes\Admin\Mail;

use App\Contracts\Admin\Mail\IncomingMailDriver;
use App\Data\Admin\Mail\IncomingCursorData;
use App\Data\Admin\Mail\IncomingFetchResultData;
use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Data\Admin\Mail\NormalizedInboundMessageData;
use App\Enums\Admin\Mail\IncomingAcknowledgeAction;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Models\Admin\Mail\MailboxChannel;
use LogicException;
use Throwable;

class FakeIncomingMailDriver implements IncomingMailDriver
{
    public function __construct(
        public MailConnectionTestResultData $testResult,
        public ?Throwable $testException = null,
    ) {
    }

    public function driver(): MailboxDriver
    {
        return MailboxDriver::Imap;
    }

    public function test(
        MailboxChannel $channel
    ): MailConnectionTestResultData {
        if ($this->testException !== null) {
            throw $this->testException;
        }

        return $this->testResult;
    }

    public function fetch(
        MailboxChannel $channel,
        ?IncomingCursorData $cursor = null,
        int $limit = 100,
    ): IncomingFetchResultData {
        throw new LogicException(
            'Fake incoming driver does not implement fetch().'
        );
    }

    public function acknowledge(
        MailboxChannel $channel,
        NormalizedInboundMessageData $message,
        IncomingAcknowledgeAction $action,
    ): void {
        throw new LogicException(
            'Fake incoming driver does not implement acknowledge().'
        );
    }

    public function acknowledgeMany(
        MailboxChannel $channel,
        array $messages,
        IncomingAcknowledgeAction $action,
    ): int {
        throw new LogicException(
            'Fake incoming driver does not implement acknowledgeMany().'
        );
    }
}
