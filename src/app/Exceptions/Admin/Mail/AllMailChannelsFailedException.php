<?php

namespace App\Exceptions\Admin\Mail;

use App\Enums\Admin\Mail\MailboxChannelDirection;
use RuntimeException;

class AllMailChannelsFailedException extends RuntimeException
{
    public function __construct(
        private readonly int $mailboxId,
        private readonly MailboxChannelDirection $direction,
        private readonly array $failures,
    ) {
        parent::__construct(
            "All {$direction->value} channels failed "
            . "for mailbox [{$mailboxId}]."
        );
    }

    public function mailboxId(): int
    {
        return $this->mailboxId;
    }

    public function direction(): MailboxChannelDirection
    {
        return $this->direction;
    }

    public function failures(): array
    {
        return $this->failures;
    }
}
