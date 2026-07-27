<?php

namespace App\Enums\Admin\Mail;

enum IncomingAcknowledgeAction: string
{
    case Keep = 'keep';
    case MarkRead = 'mark_read';
    case Move = 'move';
    case Delete = 'delete';
}
