<?php

namespace App\Enums\Mail;

enum MailboxHealthStatus: string
{
    case Unknown = 'unknown';
    case Healthy = 'healthy';
    case Warning = 'warning';
    case Failed = 'failed';
    case Disabled = 'disabled';
}
