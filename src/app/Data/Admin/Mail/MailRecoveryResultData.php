<?php

namespace App\Data\Admin\Mail;

final readonly class MailRecoveryResultData
{
    public function __construct(
        public int $incomingStuckReset,
        public int $incomingReceivedDispatched,
        public int $outgoingStuckReset,
        public int $outgoingQueuedDispatched,
    ) {
    }

    public function totalActions(): int
    {
        return $this->incomingStuckReset
            + $this->incomingReceivedDispatched
            + $this->outgoingStuckReset
            + $this->outgoingQueuedDispatched;
    }
}
