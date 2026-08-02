<?php

namespace App\Enums\Admin\Mail;

enum EmailQuarantineResolution: string
{
    case Retried = 'retried';

    case Ignored = 'ignored';

    case Resolved = 'resolved';
}
