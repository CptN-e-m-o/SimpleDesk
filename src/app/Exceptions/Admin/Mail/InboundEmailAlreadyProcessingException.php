<?php

namespace App\Exceptions\Admin\Mail;

class InboundEmailAlreadyProcessingException extends InboundEmailTicketingException
{
    public function __construct(
        int $emailMessageId
    ) {
        parent::__construct(
            message: "Inbound email message [{$emailMessageId}] "
            .'is already being processed.',
            errorCode: 'inbound_email_already_processing',
            retryable: true,
        );
    }
}
