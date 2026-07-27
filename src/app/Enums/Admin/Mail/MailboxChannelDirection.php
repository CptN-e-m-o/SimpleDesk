<?php

namespace App\Enums\Admin\Mail;

enum MailboxChannelDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
}
