<?php

namespace App\Enums\Mail;

enum MailboxChannelDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
}
