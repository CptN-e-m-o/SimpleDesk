<?php

namespace App\Enums\Admin\Mail;

enum MailAdminAuditStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Rejected = 'rejected';
}
