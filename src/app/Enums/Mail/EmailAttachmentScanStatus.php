<?php

namespace App\Enums\Mail;

enum EmailAttachmentScanStatus: string
{
    case NotScanned = 'not_scanned';
    case Pending = 'pending';
    case Clean = 'clean';
    case Infected = 'infected';
    case Failed = 'failed';
}
