<?php

namespace App\Enums\Mail;

enum EmailMessageDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
}
