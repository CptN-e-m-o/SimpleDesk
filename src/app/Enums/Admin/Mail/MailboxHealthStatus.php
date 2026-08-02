<?php

namespace App\Enums\Admin\Mail;

enum MailboxHealthStatus: string
{
    case Unknown = 'unknown';
    case Healthy = 'healthy';
    case Warning = 'warning';
    case Failed = 'failed';
    case Disabled = 'disabled';
}
