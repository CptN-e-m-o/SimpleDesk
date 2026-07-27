<?php

namespace App\Data\Mail;

use App\Models\Admin\Mail\EmailMessage;

final readonly class PersistedInboundMessageData
{
    public function __construct(
        public EmailMessage $emailMessage,
        public bool $created,
        public bool $duplicate,
    ) {
    }
}
