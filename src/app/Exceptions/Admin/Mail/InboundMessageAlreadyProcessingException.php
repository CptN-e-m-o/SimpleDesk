<?php

namespace App\Exceptions\Admin\Mail;

use RuntimeException;

class InboundMessageAlreadyProcessingException extends RuntimeException
{
    public function __construct(
        int $emailMessageId,
    ) {
        parent::__construct(
            "Inbound email message [{$emailMessageId}] "
            .'is already being persisted.'
        );
    }
}
