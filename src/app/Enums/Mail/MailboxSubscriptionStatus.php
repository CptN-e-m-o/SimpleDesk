<?php

namespace App\Enums\Mail;

enum MailboxSubscriptionStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Failed = 'failed';
}
