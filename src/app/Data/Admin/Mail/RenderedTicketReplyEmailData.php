<?php

namespace App\Data\Admin\Mail;

final readonly class RenderedTicketReplyEmailData
{
    public function __construct(
        public string $subject,
        public string $textBody,
        public string $htmlBody,
    ) {
    }
}
