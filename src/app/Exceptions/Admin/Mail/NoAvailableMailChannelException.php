<?php

namespace App\Exceptions\Admin\Mail;

use App\Enums\Admin\Mail\MailboxChannelDirection;
use RuntimeException;

class NoAvailableMailChannelException extends RuntimeException
{
    public function __construct(
        int $mailboxId,
        MailboxChannelDirection $direction,
    ) {
        parent::__construct(
            "Mailbox [{$mailboxId}] has no available "
            . "{$direction->value} channels."
        );
    }
}
