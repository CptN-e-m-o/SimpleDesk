<?php

namespace App\Enums\Admin\Mail;

enum EmailMessageDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
}
