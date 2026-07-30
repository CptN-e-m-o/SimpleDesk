<?php

namespace App\Data\Admin\Mail;

use App\Enums\Admin\Mail\MailAdminAuditSubjectType;

final readonly class MailAdminAuditTargetData
{
    public function __construct(
        public ?MailAdminAuditSubjectType $subjectType,
        public ?int $subjectId,
        public ?int $mailboxId,
    ) {
    }
}
